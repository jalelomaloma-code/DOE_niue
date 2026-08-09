<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Enums\ContentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('path')
                    ->searchable(),
                TextColumn::make('parent.title')
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('show_in_section_nav')
                    ->boolean(),
                TextColumn::make('status')
                    ->badge()
                    // Draft vs Published is the one signal that matters most on
                    // a resource whose whole point is "editors submit, managers
                    // publish" -- without a color, both render as an identical
                    // grey badge and are distinguishable only by reading the
                    // label. ContentStatus::color() already encodes the right
                    // mapping (gray/warning/success/danger).
                    ->color(fn (?ContentStatus $state) => $state?->color())
                    ->searchable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('seo_title')
                    ->searchable(),
                IconColumn::make('is_demo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // Pages have a parent_id/sort_order hierarchy, not a flat list --
            // `path` groups children directly under their parents (e.g.
            // "our-work" then "our-work/waste-and-recycling") and is a single
            // indexed column, unlike sorting by id which scatters a tree
            // across insertion order.
            ->defaultSort('path')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
