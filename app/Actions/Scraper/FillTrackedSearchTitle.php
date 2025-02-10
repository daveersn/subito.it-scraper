<?php

namespace App\Actions\Scraper;

use App\Models\TrackedSearch;
use App\Scraper\Scraper;
use HeadlessChromium\Browser;
use Lorisleiva\Actions\Concerns\AsAction;

class FillTrackedSearchTitle
{
    use AsAction;

    public function handle(TrackedSearch $search)
    {
        if ($search->name) {
            return;
        }

        $scraper = Scraper::make([
            'headless' => false,
            'windowSize' => [1920, 1080],
        ]);

        $title = $scraper->wrap(function (Browser $browser) {
            $page = $browser->getPages()[0] ?? $browser->createPage();

            return $page->evaluate('document.title')->getReturnValue();
        });

        $search->update(['name' => $title]);
    }
}
