<?php

namespace App\Filament\Pages;

use App\Models\HomepageSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageHomepage extends Page
{
    protected string $view = 'filament.pages.manage-homepage';

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Homepage';

    protected static ?string $title = 'Homepage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    public ?HomepageSetting $record = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * Restricted to Super Admin and Website Manager. An Editor must not be
     * able to rewrite the homepage hero.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->mayPublish() ?? false;
    }

    public function mount(): void
    {
        $this->record = HomepageSetting::current();

        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Hero')
                    ->components([
                        TextInput::make('hero_headline')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('hero_intro')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('hero_primary_cta_label'),
                        TextInput::make('hero_primary_cta_url'),
                        TextInput::make('hero_secondary_cta_label'),
                        TextInput::make('hero_secondary_cta_url'),
                        SpatieMediaLibraryFileUpload::make('hero_image')
                            ->collection('hero_image')
                            ->image()
                            ->columnSpanFull(),
                    ]),
                Section::make('Section headings and intros')
                    ->components([
                        TextInput::make('quick_links_heading'),
                        TextInput::make('programmes_heading'),
                        Textarea::make('programmes_intro')
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('news_heading'),
                        TextInput::make('projects_heading'),
                        TextInput::make('resources_heading'),
                    ]),
                Section::make('Report an issue')
                    ->components([
                        TextInput::make('report_heading'),
                        Textarea::make('report_intro')
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('report_cta_label'),
                        TextInput::make('report_cta_url'),
                    ]),
            ])
            ->model($this->record)
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $this->record->update($this->form->getState());

        Notification::make()
            ->title('Homepage settings saved')
            ->success()
            ->send();
    }
}
