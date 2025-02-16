<?php

namespace App\Actions\Scraper;

use App\Actions\Concerns\PrintsPrettyJson;
use App\Models\TrackedSearch;
use App\Scraper\Scraper;
use HeadlessChromium\Page;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;

class FillTrackedSearchTitle
{
    use AsAction, PrintsPrettyJson;

    public $commandSignature = 'tracked-search:scrape-title {id} {--f|force}';

    public function handle(TrackedSearch $search, bool $force = false): TrackedSearch
    {
        if ($search->name && ! $force) {
            return $search;
        }

        $scraper = Scraper::make([
            'headless' => false,
            'windowSize' => [1920, 1080],
        ]);

        $title = $scraper->wrap(function (Page $page) use ($search) {
            $page->navigate($search->url)->waitForNavigation();

            return $page->evaluate('document.title')->getReturnValue();
        });

        $search->update(['name' => $title]);

        return $search;
    }

    public function asCommand(Command $command): void
    {
        try {
            $result = $this->handle(TrackedSearch::find($command->argument('id')), $command->option('force'));
            $this->printPrettyJson($result, $command);
        } catch (\Exception $e) {
            $command->error($e->getMessage());
        }
    }
}
