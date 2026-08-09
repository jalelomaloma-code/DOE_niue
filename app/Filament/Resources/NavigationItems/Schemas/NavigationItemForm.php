<?php

namespace App\Filament\Resources\NavigationItems\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class NavigationItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->required(),
                TextInput::make('url')
                    ->label('URL')
                    ->helperText('A site-relative path, e.g. /waste-and-recycling, or a full https:// URL.')
                    ->required()
                    // Deliberately narrower than Filament's own ->url() (which
                    // requires a scheme and would reject every seeded
                    // site-relative path). Accepts only a single-leading-slash
                    // relative path or an http(s) URL; explicitly excludes
                    // javascript:, data: and protocol-relative "//host" forms,
                    // since this value is rendered into an <a href="..."> in
                    // the header and footer and Blade's {{ }} does not
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
}
