<?php

namespace App\Actions;

use App\Actions\Scraper\ScrapeSubitoPage;
use App\DTO\Items\BaseItem;
use App\Models\Item;
use App\Models\TrackedSearch;
use App\Support\Scraper;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use HeadlessChromium\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class TrackSearch
{
    use AsAction;

    public string $commandSignature = 'track-search:run {id}';

    public int $jobTries = 1;

    public $jobUniqueFor = 300;

    public function handle(TrackedSearch $search): void
    {
        $scraper = Scraper::make([
            'windowSize' => [1920, 1080],
        ]);

        /** @var Collection $items */
        $items = $scraper->wrap(fn (Page $page) => ScrapeSubitoPage::run(
            $page,
            $search->url
        ));

        $searchItems = $search->items()
            ->withTrashed()
            ->get();

        $items->each(function (BaseItem $item) use ($searchItems, $search) {
            $model = $searchItems->where('item_id', $item->item_id);

            if ($model->count()) {
                $searchItems->forget($model->keys()->first());
                /** @var Item $model */
                $model = $model->first();
                // Item is found again, restore it
                $model->deleted_at = null;
            } else {
                $model = Item::make();
            }

            // Convert BaseItem to array
            $itemData = $item->except('price')->toArray();

            $model
                ->fill(
                    array_merge(
                        $itemData,
                        ['tracked_search_id' => $search->getKey()]
                    ))
                ->save();

            // If price changes, attach a new price record
            if (! $model->price()->exists() || $model->price->value->getAmount() !== $item->price->getAmount()) {
                $model->price()->create(['value' => $item->price]);
            }
        });

        $searchItems->each(fn (Item $item) => $item->delete());

        $search->update([
            'last_run_at' => now(),
        ]);

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

    public function asJob(TrackedSearch $search, bool $withSchedule = false)
    {
        $this->handle($search);

        if (! $withSchedule || ! $search->schedule) {
            return;
        }

        $search->update([
            'next_scheduled_at' => $search->schedule->getNextRunDate(),
            'last_schedule_run_at' => now(),
        ]);
    }
}
