<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Generates deterministic placeholder graphics for demo content, so nothing
 * photographic is sourced, downloaded or committed to the repository. Every
 * image is a gradient drawn from the project palette with a small "DEMO —
 * <subject>" label baked in, so a placeholder is unmistakable on sight and
 * nobody at a client review mistakes it for real Department photography.
 *
 * Deterministic: the same subject + label + dimensions always produces the
 * same bytes. There is no randomness, no timestamp and no network call, so
 * `php artisan migrate:fresh --seed` behaves identically offline every time.
 */
class DemoImageGenerator
{
    /**
     * Palette pairings, top colour first. Chosen to keep each subject
     * visually distinct while staying inside the brand palette:
     * - marine: Pacific Blue -> Environment Green (ocean/coastline/reef)
     * - forest: Environment Green -> Deep Navy (forest/conservation/biodiversity)
     * - waste: Deep Navy -> Pacific Blue (industrial/utility feel, distinct
     *   from both of the above)
     * - climate: Pacific Blue -> Deep Navy (sky-to-dusk, atmosphere)
     */
    private const PALETTES = [
        'marine' => ['#003A70', '#287A4B'],
        'forest' => ['#287A4B', '#142B3A'],
        'waste' => ['#142B3A', '#003A70'],
        'climate' => ['#003A70', '#142B3A'],
    ];

    /**
     * Generate (or reuse a cached) placeholder JPEG and return its absolute path.
     *
     * @param  string  $palette  One of the keys in self::PALETTES.
     * @param  string  $label  Short caption baked into the image, e.g. "DEMO — coastline".
     */
    public static function make(string $palette, string $label, int $width, int $height): string
    {
        [$topHex, $bottomHex] = self::PALETTES[$palette] ?? self::PALETTES['marine'];

        $dir = storage_path('app/demo-generated/images');
        File::ensureDirectoryExists($dir);

        // Filename is a pure function of the inputs, so re-running the seeder
        // reuses an already-generated file instead of re-rendering it, and
        // two records with identical inputs deterministically share bytes.
        $filename = Str::slug("{$palette}-{$label}-{$width}x{$height}").'.jpg';
        $path = $dir.DIRECTORY_SEPARATOR.$filename;

        if (! file_exists($path)) {
            self::render($path, $topHex, $bottomHex, $label, $width, $height);
        }

        return $path;
    }

    private static function render(string $path, string $topHex, string $bottomHex, string $label, int $width, int $height): void
    {
        [$topR, $topG, $topB] = self::hexToRgb($topHex);
        [$bottomR, $bottomG, $bottomB] = self::hexToRgb($bottomHex);

        $image = imagecreatetruecolor($width, $height);

        // Vertical gradient, one filled row at a time: fast, and a pure
        // function of (y, height) so it is reproducible pixel for pixel.
        for ($y = 0; $y < $height; $y++) {
            $t = $height > 1 ? $y / ($height - 1) : 0;
            $r = (int) round($topR + ($bottomR - $topR) * $t);
            $g = (int) round($topG + ($bottomG - $topG) * $t);
            $b = (int) round($topB + ($bottomB - $topB) * $t);
            $rowColor = imagecolorallocate($image, $r, $g, $b);
            imagefilledrectangle($image, 0, $y, $width - 1, $y, $rowColor);
        }

        self::drawLabel($image, $label, $width, $height);

        imagejpeg($image, $path, 82);
        imagedestroy($image);
    }

    /**
     * Draws a solid Deep Navy bar across the bottom of the image with the
     * label rendered in white, scaled up from GD's built-in bitmap font so
     * it stays legible at both card and hero sizes without bundling a font
     * file.
     */
    private static function drawLabel($image, string $label, int $width, int $height): void
    {
        $navy = imagecolorallocate($image, 0x14, 0x2B, 0x3A);
        $barHeight = max(40, (int) round($height * 0.09));
        $barY = $height - $barHeight;
        imagefilledrectangle($image, 0, $barY, $width - 1, $height - 1, $navy);

        // GD's built-in bitmap fonts only understand Latin-1 bytes. A raw
        // UTF-8 label (e.g. containing an em dash, "—") gets read as several
        // separate Latin-1 bytes and renders as garbage. Transliterate to
        // plain ASCII first so any caller-supplied label is safe.
        $safeLabel = self::toAscii($label);

        $font = 5; // largest GD built-in bitmap font
        $charWidth = imagefontwidth($font);
        $charHeight = imagefontheight($font);
        $textWidth = max(1, strlen($safeLabel) * $charWidth);

        // Render the label to a small buffer first, then scale it up with
        // nearest-neighbour resampling. Blocky, but far more legible at
        // 1920px width than the native ~9px-tall bitmap font would be.
        $buffer = imagecreatetruecolor($textWidth, $charHeight);
        imagefilledrectangle($buffer, 0, 0, $textWidth, $charHeight, $navy);
        $white = imagecolorallocate($buffer, 255, 255, 255);
        imagestring($buffer, $font, 0, 0, $safeLabel, $white);

        $scale = $width >= 1600 ? 3 : 2;
        $scaledWidth = $textWidth * $scale;
        $scaledHeight = $charHeight * $scale;
        $destX = max(16, (int) round($width * 0.02));
        $destY = $barY + (int) (($barHeight - $scaledHeight) / 2);

        imagecopyresized(
            $image, $buffer,
            $destX, $destY, 0, 0,
            $scaledWidth, $scaledHeight, $textWidth, $charHeight,
        );

        imagedestroy($buffer);
    }

    /**
     * Best-effort UTF-8 -> ASCII transliteration for GD's Latin-1-only
     * bitmap fonts. iconv's TRANSLIT turns "—" into "-", accented letters
     * into their unaccented form, etc.; anything it can't map is dropped
     * rather than left as raw multi-byte garbage.
     */
    private static function toAscii(string $text): string
    {
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        return $transliterated !== false ? $transliterated : preg_replace('/[^\x20-\x7E]/', '', $text);
    }

    /** @return array{int, int, int} */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
