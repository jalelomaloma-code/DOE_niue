<?php

namespace App\Filament\Widgets;

use App\Models\NewsArticle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * The newest few articles regardless of status -- an editor needs to see
 * what's sitting in draft or under review here just as much as what's
 * live. Not published()-scoped.
 *
 * Sort 30. Room is left at 35-39 for a Spec 4 "Recent Environmental
 * Reports" widget alongside this one.
 */
class RecentNews extends TableWidget
{
    protected static ?int $sort = 30;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => NewsArticle::query()
                ->with('category')
                ->latest()
                ->limit(5))
            // A fixed "latest 5" list has no meaningful page 2, and
            // ->paginated(false) skips the COUNT(*) query pagination
            // would otherwise add.
            ->paginated(false)
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->placeholder('Uncategorised'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime()
                    ->placeholder('Not published'),
            ]);
    }
}
