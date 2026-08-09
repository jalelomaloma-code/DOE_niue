<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Enums\ContentStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DocumentForm
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
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Select::make('document_category_id')
                    ->relationship('category', 'name')
                    ->required(),
                SpatieMediaLibraryFileUpload::make('file')
                    ->collection('file')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(20480)
                    ->required(),
                DatePicker::make('published_date'),
                Toggle::make('is_featured'),
                // This closure IS the publish control. Filament derives a
                // server-side `in:` rule from whichever options it returns,
                // and re-evaluates it per request against the acting user --
                // so an Editor who posts `published` anyway is rejected by
                // Laravel's own validation, not by a check further in.
                // Weaken the mayPublish() test below and the enforcement goes
                // with it; there is no second line of defence. DocumentPolicy
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
            ]);
    }
}
