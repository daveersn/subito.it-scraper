<?php

namespace App\Actions;

use App\DTO\Items\BaseItem;
use App\Models\Item;
use App\Models\TrackedSearch;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class TrackSearch
{
    use AsAction;

    public string $commandSignature = 'track-search:run {id}';

    public function handle(TrackedSearch $search): void
    {
        /** @var Collection $items */
        $items = ScrapeSubitoPage::run($search->url);

        $items->each(function (BaseItem $item) {
            Item::updateOrCreate(
                ['id' => $item->id],
                $item->toArray()
            );
        });

        Notification::make('search_tracking_completed')
            ->color(Color::Green)
            ->title('Search tracking completed')
            ->body("Search tracking for '".($search->name ?? $search->url)."' completed successfully")
            ->sendToDatabase($search->user);
    }

    public function asCommand(Command $command)
    {
        $trackedSearch = TrackedSearch::find($command->argument('id'));

        $this->handle($trackedSearch);
    }
}
