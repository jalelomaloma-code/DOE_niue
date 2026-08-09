<?php

namespace App\Filament\Resources\TeamMembers\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TeamMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('role')
                    ->required(),
                Textarea::make('bio')
                    ->columnSpanFull(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                SpatieMediaLibraryFileUpload::make('photo')
                    ->collection('photo')
                    ->image()
                    ->maxSize(5120)
                    ->customProperties(fn (Get $get): array => ['alt' => $get('photo_alt')]),
                TextInput::make('photo_alt')
                    ->label('Alt text')
                    ->helperText('Describes the photo for screen readers and search engines. Some staff may prefer not to have their photo published -- leave the photo field empty in that case.')
                    ->afterStateHydrated(fn ($component, $record) => $component->state($record?->photoAlt()))
                    ->required(fn (Get $get): bool => filled($get('photo')))
                    ->maxLength(255)
                    ->dehydrated(false),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->helperText('Switch off when someone leaves the Department, instead of deleting their record.'),
            ]);
    }
}
