<?php

namespace App\Filament\Resources;

use App\Actions\TrackSearch;
use App\Filament\Resources\TrackedSearchResource\Pages;
use App\Models\TrackedSearch;
use Cknow\Money\Money;
use Cron\CronExpression;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class TrackedSearchResource extends Resource
{
    protected static ?string $model = TrackedSearch::class;

    protected static ?string $slug = 'tracked-searches';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),

                TextInput::make('url')
                    ->required(),

                TextInput::make('schedule')
                    ->live()
                    ->formatStateUsing(fn (?CronExpression $state, $record) => $state?->getExpression()),
                Placeholder::make('next_scheduled_at')
                    ->content(function (?TrackedSearch $record, Get $get) {
                        if ($record && $record->next_scheduled_at) {
                            return $record->next_scheduled_at->format('d/m/Y H:i');
                        }

                        $schedule = $get('schedule');

                        try {
                            return $schedule
                                ? (new CronExpression($schedule))?->getNextRunDate()->format('d/m/Y H:i')
                                : null;
                        } catch (\InvalidArgumentException) {
                            return new HtmlString("<span class='text-danger-600 font-semibold'>Invalid schedule</span>");
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'default' => 2,
            ])
            ->selectable(false)
            ->columns([
                Stack::make([
                    TextColumn::make('name')
                        ->searchable()
                        ->formatStateUsing(fn (string $state) => new HtmlString("<span class='text-xl font-bold'>$state</span>"))
                        ->sortable(),

                    Split::make([
                        TextColumn::make('items_count')
                            ->grow(false)
                            ->formatStateUsing(fn (?int $state) => $state ? "$state items" : null),
                        TextColumn::make('last_run_at')
                            ->formatStateUsing(fn (?Carbon $state) => $state ? "Ultima ricerca: {$state->diffForHumans()}" : null),
                    ])
                        ->extraAttributes(['class' => 'mt-2']),

                    Split::make([
                        TextColumn::make('prices_avg_value')
                            ->formatStateUsing(fn (?int $state) => $state ? new HtmlString('<p class="text-gray-500">Average price:</p><p>'.new Money($state).'</p>') : null),

                        TextColumn::make('prices_min_value')
                            ->formatStateUsing(fn (?int $state) => $state ? new HtmlString('<p class="text-gray-500">Minimum price:</p><p>'.new Money($state).'</p>') : null),

                        TextColumn::make('prices_max_value')
                            ->formatStateUsing(fn (?int $state) => $state ? new HtmlString('<p class="text-gray-500">Maximum price:</p><p>'.new Money($state).'</p>') : null),
                    ])
                        ->extraAttributes(['class' => 'mt-6']),

                    TextColumn::make('next_scheduled_at')
                        ->formatStateUsing(fn (?Carbon $state) => $state ? new HtmlString("Next scheduled at: {$state->diffForHumans()}") : null),
                ]),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('run_scraping')
                    ->label('Scrape')
                    ->color(Color::Indigo)
                    ->icon('heroicon-o-magnifying-glass')
                    ->action(function (TrackedSearch $record) {
                        TrackSearch::dispatch($record);

                        Notification::make()
                            ->icon('heroicon-o-magnifying-glass')
                            ->title('Search tracking has started')
                            ->body("Search tracking for '".($record->name ?? $record->url)."' has started.\n You will receive a notification when is the tracking has finished")
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrackedSearches::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('items')
            ->withAvg('prices', 'value')
            ->withMax('prices', 'value')
            ->withMin('prices', 'value')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
