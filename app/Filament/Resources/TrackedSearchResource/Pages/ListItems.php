<?php

namespace App\Filament\Resources\TrackedSearchResource\Pages;

use App\Filament\Resources\TrackedSearchResource;
use App\Filament\Resources\TrackedSearchResource\Widgets\ItemPricesChart;
use App\Models\Item;
use App\Models\TrackedSearch;
use Carbon\Carbon;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ListItems extends ListRecords
{
    protected static string $resource = TrackedSearchResource::class;

    public TrackedSearch $record;

    public function getTitle(): string|Htmlable
    {
        return 'Annunci';
    }

    public function getBreadcrumb(): ?string
    {
        return $this->record->name;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ItemPricesChart::make(['trackedSearch' => $this->record]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->record->items()->getQuery())
            ->columns([
                TextColumn::make('title')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price.value')
                    ->sortable()
                    ->label('Prezzo'),

                TextColumn::make('town')
                    ->searchable()
                    ->label('Città'),

                TextColumn::make('uploadedDateTime')
                    ->label('Data caricamento')
                    ->sortable()
                    ->formatStateUsing(fn (?Carbon $state) => $state?->format('d/m/Y')),

                TextColumn::make('status')
                    ->badge()
                    ->label('Stato'),
            ])
            ->actions([
                Action::make('open_item')
                    ->url(fn (Item $record) => $record->link)
                    ->openUrlInNewTab()
                    ->iconButton()
                    ->iconSize(IconSize::Large)
                    ->icon('heroicon-o-arrow-top-right-on-square'),
            ]);
    }
}
