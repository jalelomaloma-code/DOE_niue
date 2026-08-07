<?php

namespace App\Filament\Resources\Programmes\Schemas;

use App\Enums\ContentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProgrammeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Textarea::make('summary')
                    ->rows(3)
                    ->maxLength(300)
                    ->columnSpanFull(),
                RichEditor::make('body')
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('featured_image')
                    ->collection('featured_image')
                    ->image()
                    ->customProperties(fn (Get $get): array => ['alt' => $get('featured_image_alt')]),
                TextInput::make('featured_image_alt')
                    ->label('Alt text')
                    ->helperText('Describes the image for screen readers and search engines.')
                    ->required(fn (Get $get): bool => filled($get('featured_image')))
                    ->maxLength(255)
                    ->dehydrated(false),
                Toggle::make('is_featured'),
                Select::make('status')
                    ->options(function (): array {
                        $options = ContentStatus::options();

                        if (! auth()->user()->mayPublish()) {
                            unset($options[ContentStatus::Published->value]);
                        }

                        return $options;
                    })
                    ->default(ContentStatus::Draft->value)
                    ->required(),
                DateTimePicker::make('published_at'),
                Section::make('SEO')
                    ->components([
                        TextInput::make('seo_title'),
                        Textarea::make('seo_description')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
