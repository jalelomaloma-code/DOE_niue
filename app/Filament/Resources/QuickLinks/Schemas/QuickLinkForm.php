<?php

namespace App\Filament\Resources\QuickLinks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class QuickLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->required(),
                TextInput::make('description'),
                Select::make('icon')
                    ->label('Icon')
                    ->options(static::heroiconOptions())
                    ->searchable()
                    ->native(false),
                TextInput::make('url')
                    ->label('URL')
                    ->helperText('A site-relative path, e.g. /waste-and-recycling, or a full https:// URL.')
                    ->required()
                    // Deliberately narrower than Filament's own ->url() (which
                    // requires a scheme and would reject every seeded
                    // site-relative path). Accepts only a single-leading-slash
                    // relative path or an http(s) URL; explicitly excludes
                    // javascript:, data: and protocol-relative "//host" forms,
                    // since this value is rendered into an <a href="..."> on
                    // the public homepage and Blade's {{ }} does not
                    // neutralise a javascript: scheme.
                    ->regex('/^(\/(?!\/)\S*|https?:\/\/\S+)$/')
                    ->validationMessages([
                        'regex' => 'The URL must be a site-relative path starting with / (not //) or a full http(s):// URL.',
                    ])
                    ->unique(ignoreRecord: true),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }

    /**
     * Outline Heroicons, keyed by the Blade component name stored on the
     * model (e.g. "heroicon-o-globe-alt"), so a typo can't silently render
     * nothing.
     *
     * @return array<string, string>
     */
    protected static function heroiconOptions(): array
    {
        return collect(Heroicon::cases())
            ->filter(fn (Heroicon $icon) => str_starts_with($icon->value, 'o-'))
            ->mapWithKeys(fn (Heroicon $icon) => [
                "heroicon-{$icon->value}" => (string) Str::of($icon->name)->after('Outlined')->headline(),
            ])
            ->sort()
            ->all();
    }
}
