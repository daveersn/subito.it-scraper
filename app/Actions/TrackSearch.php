<?php

namespace App\Actions;

use App\Actions\Scraper\ScrapeSubitoPage;
use App\DTO\Items\BaseItem;
use App\Models\Item;
use App\Models\TrackedSearch;
use App\Scraper\Scraper;
use Cron\CronExpression;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use HeadlessChromium\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Decorators\JobDecorator;

class TrackSearch
{
    use AsAction;

    public string $commandSignature = 'track-search:run {id}';

    public int $jobTries = 1;

    public $jobUniqueFor = 300;

    public function handle(TrackedSearch $search): void
    {
        $scraper = Scraper::make([
            'headless' => false,
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

            $model
                ->fill(
                    array_merge(
                        $item->except('price')->toArray(),
                        ['tracked_search_id' => $search->getKey()]
                    ))
                ->save();

            // If price changes, attach a new price record
            if (! $model->price()->exists() || $model->price->value !== $item->price) {
                $model->price()->create(['value' => $item->price]);
            }
        });

        $searchItems->each(fn (Item $item) => $item->delete());
    }

    public function asCommand(Command $command)
    {
        $trackedSearch = TrackedSearch::find($command->argument('id'));

        $this->handle($trackedSearch);
    }

    public function asJob(TrackedSearch $search, ?string $schedule = null, bool $selfDispatched = false)
    {
        $this->handle($search);

        Notification::make('search_tracking_completed')
            ->color(Color::Green)
            ->title('Search tracking completed')
            ->body("Search tracking for '".($search->name ?? $search->url)."' completed successfully")
            ->sendToDatabase($search->user);

        // If scheduled, dispatch next delayed job
        if ($schedule && $schedule == $search->schedule) {
            self::dispatch($search, $search->schedule, true);
        }
    }

    public function configureJob(JobDecorator $job)
    {
        $params = $job->getParameters();
        $schedule = $params[1] ?? null;
        $selfDispatched = $params[2] ?? null;

        if (! $schedule || ! $selfDispatched) {
            return;
        }

        $schedule = new CronExpression($schedule);

        try {
            $job->delay($schedule->getNextRunDate());
        } catch (\RuntimeException $exception) {
            report($exception);
        }
    }
}
