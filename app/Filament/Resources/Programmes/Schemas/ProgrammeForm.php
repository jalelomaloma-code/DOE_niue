<?php

namespace App\Filament\Resources\Programmes\Schemas;

use App\Enums\ContentStatus;
use App\Support\RichTextSanitiser;
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
                // The ceiling has to live here, not in RichTextSanitiser: the
                // sanitiser's own cap truncated silently (and could blank the
                // body outright on a multibyte boundary), so it was removed.
                // A `max:` rule is the version of that guard an officer can
                // see and respond to. See RichTextSanitiser::MAX_LENGTH.
                RichEditor::make('body')
                    ->maxLength(RichTextSanitiser::MAX_LENGTH)
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('featured_image')
                    ->collection('featured_image')
                    ->image()
                    ->maxSize(5120)
                    ->customProperties(fn (Get $get): array => ['alt' => $get('featured_image_alt')]),
                TextInput::make('featured_image_alt')
                    ->label('Alt text')
                    ->helperText('Describes the image for screen readers and search engines.')
                    ->afterStateHydrated(fn ($component, $record) => $component->state($record?->featuredImageAlt()))
                    ->required(fn (Get $get): bool => filled($get('featured_image')))
                    ->maxLength(255)
                    ->dehydrated(false),
                Toggle::make('is_featured'),
                // This closure IS the publish control. Filament derives a
                // server-side `in:` rule from whichever options it returns,
                // and re-evaluates it per request against the acting user --
                // so an Editor who posts `published` anyway is rejected by
                // Laravel's own validation, not by a check further in.
                // Weaken the mayPublish() test below and the enforcement goes
                // with it; there is no second line of defence. ProgrammePolicy
                // deliberately has no publish() ability for this reason.
                Select::make('status')
                    ->options(function (): array {
                        $options = ContentStatus::options();

                        if (! (auth()->user()?->mayPublish() ?? false)) {
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
