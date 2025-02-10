<?php

namespace App\Actions;

use App\Actions\Scraper\ScrapeSubitoPage;
use App\DTO\Items\BaseItem;
use App\Models\Item;
use App\Models\TrackedSearch;
use App\Scraper\Scraper;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use HeadlessChromium\Browser;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class TrackSearch
{
    use AsAction;

    public string $commandSignature = 'track-search:run {id}';

    public function handle(TrackedSearch $search): void
    {
        $scraper = Scraper::make([
            'headless' => false,
            'windowSize' => [1920, 1080],
        ]);

        /** @var Collection $items */
        $items = $scraper->wrap(fn (Browser $browser) => ScrapeSubitoPage::run(
            $browser,
            $search->url
        ));

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
