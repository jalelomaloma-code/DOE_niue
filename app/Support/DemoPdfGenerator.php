<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Generates a minimal, genuinely valid single-page PDF for demo Document
 * records, written by hand as raw PDF syntax (no network call, no bundled
 * library). It opens correctly in a PDF reader — unlike a file that merely
 * starts with the "%PDF-" magic bytes — so a client clicking a demo download
 * during review sees a real, readable document rather than an error.
 *
 * Deterministic: identical title/category/body always produce identical bytes.
 */
class DemoPdfGenerator
{
    /**
     * @param  string[]  $bodyLines
     */
    public static function make(string $title, string $category, array $bodyLines): string
    {
        $dir = storage_path('app/demo-generated/documents');
        File::ensureDirectoryExists($dir);

        $path = $dir.DIRECTORY_SEPARATOR.Str::slug($title).'.pdf';

        if (! file_exists($path)) {
            file_put_contents($path, self::render($title, $category, $bodyLines));
        }

        return $path;
    }

    /**
     * @param  string[]  $bodyLines
     */
    private static function render(string $title, string $category, array $bodyLines): string
    {
        $lines = [
            "DEMO PLACEHOLDER - {$category}",
            $title,
            '',
            ...$bodyLines,
            '',
            'This is placeholder content generated for the Niue Department of',
            'Environment demo site. It is not a real Department publication and',
            'is removed by php artisan demo:purge before real content is added.',
        ];

        $stream = "BT\n/F1 14 Tf\n50 740 Td\n18 TL\n";
        foreach ($lines as $index => $line) {
            $escaped = self::escapePdfString($line);
            $stream .= $index === 0 ? "({$escaped}) Tj\n" : "T*\n({$escaped}) Tj\n";
        }
        $stream .= "ET\n";

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] '.
                 '/Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            5 => '<< /Length '.strlen($stream)." >>\nstream\n{$stream}endstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$body}\nendobj\n";
        }

        $xrefStart = strlen($pdf);
        $entryCount = count($objects) + 1;

        $pdf .= "xref\n0 {$entryCount}\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($objects as $number => $body) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }

        $pdf .= "trailer\n<< /Size {$entryCount} /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefStart}\n%%EOF";

        return $pdf;
    }

    private static function escapePdfString(string $text): string
    {
        // The standard 14 fonts (Helvetica included) only have a single-byte
        // encoding. Writing raw UTF-8 bytes for anything outside ASCII (an
        // em dash, a curly quote from fake() output, ...) renders as garbled
        // glyphs, so transliterate first — same reasoning as
        // DemoImageGenerator::toAscii().
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $ascii = $ascii !== false ? $ascii : preg_replace('/[^\x20-\x7E]/', '', $text);

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $ascii);
    }
}
