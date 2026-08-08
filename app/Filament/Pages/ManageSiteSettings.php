<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageSiteSettings extends Page
{
    protected string $view = 'filament.pages.manage-site-settings';

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Site Settings';

    protected static ?string $title = 'Site Settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public ?SiteSetting $record = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * Restricted to Super Admin and Website Manager. An Editor must not be
     * able to rewrite department contact details or the footer.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->mayPublish() ?? false;
    }

    public function mount(): void
    {
        $this->record = SiteSetting::current();

        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('department_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('government_name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('address')
                    ->rows(2),
                TextInput::make('phone'),
                TextInput::make('email')
                    ->email(),
                Textarea::make('office_hours')
                    ->rows(2),
                TextInput::make('facebook_url')
                    ->url(),
                TextInput::make('youtube_url')
                    ->url(),
                Textarea::make('footer_text')
                    ->rows(3)
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('logo')
                    ->collection('logo')
                    ->image()
                    ->columnSpanFull(),
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
            ->title('Site settings saved')
            ->success()
            ->send();
    }
}
