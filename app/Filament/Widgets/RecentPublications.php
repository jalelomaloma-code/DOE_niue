<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * The newest few documents regardless of status. Not published()-scoped,
 * same reasoning as RecentNews.
 *
 * Sort 40, after RecentNews. Room is left at 45-49 for a Spec 4 "Recent
 * Contact Submissions" widget.
 */
class RecentPublications extends TableWidget
{
    protected static ?string $heading = 'Recent Website Publications';

    protected static ?int $sort = 40;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Document::query()
                // 'media' is eager-loaded so fileType()/fileSizeForHumans()
                // (called per row below) don't each issue their own query.
                ->with(['category', 'media'])
                ->latest()
                ->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->placeholder('Uncategorised'),
                TextColumn::make('file_type')
                    ->label('File Type')
                    ->getStateUsing(fn (Document $record): ?string => $record->fileType())
                    ->placeholder('No file'),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime()
                    ->placeholder('Not published'),
            ]);
    }
}
