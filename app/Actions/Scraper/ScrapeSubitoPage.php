<?php

namespace App\Actions\Scraper;

use App\Actions\Concerns\PrintsPrettyJson;
use App\Support\Scraper;
use HeadlessChromium\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class ScrapeSubitoPage
{
    use AsAction, PrintsPrettyJson;

    public string $commandSignature = 'scrape:prices {url} {--head}';

    protected bool $headless = true;

    public function handle(Page $page, string $url): Collection
    {
        $allItems = collect();
        $currentPageIndex = 1;

        do {
            try {
                // Navigate to the current page
                $page->navigate("$url&o=$currentPageIndex")->waitForNavigation();

                // Accept Cookie Banner if present
                $page->evaluate("document.querySelector('.didomi-continue-without-agreeing')?.click()");

                // Scroll down to ensure all content is loaded
                $this->scrollPage($page);

                // Extract items from the current page
                $rawItems = ExtractSubitoItemsFromPage::run($page);

                // Skip to next page if no items found
                if ($rawItems->isEmpty()) {
                    break;
                }

                // Normalize items to DTOs
                $items = NormalizeSubitoItem::run($rawItems);

                // Add items to the collection
                $allItems = $allItems->merge($items);

                // Check if there's a next page
                $hasNextPage = $this->hasNextPage($page);

                // Increment page index if there's a next page
                if ($hasNextPage) {
                    $currentPageIndex++;
                }
            } catch (\Exception $e) {
                report("Error scraping Subito.it page $currentPageIndex: {$e->getMessage()}");
                break;
            }
        } while ($this->hasNextPage($page));

        return $allItems;
    }

    public function asCommand(Command $command): void
    {
        $this->headless = ! $command->option('head');

        $scraper = Scraper::make([
            'headless' => $this->headless,
            'windowSize' => [1920, 1080],
        ]);

        $data = $scraper->wrap(fn (Page $page) => $this->handle(
            $page,
            $command->argument('url')
        ));

        $this->printPrettyJson($data, $command);
    }

    /**
     * Scroll the page to ensure all content is loaded
     */
    protected function scrollPage(Page $page): void
    {
        try {
            /** @var int $pageHeight */
            $pageHeight = $page->evaluate('document.body.scrollHeight')->getReturnValue();
            $innerHeight = $page->evaluate('window.innerHeight')->getReturnValue();

            // Scroll page n times browser inner height
            for ($i = 1; $i <= ceil($pageHeight / $innerHeight); $i++) {
                $currentHeight = $innerHeight * $i;
                $page->evaluate("window.scrollTo(0, $currentHeight)");
                usleep(0.1 * 100000); // Small delay to allow content to load
            }
        } catch (\Exception $e) {
            report("Error scrolling page: {$e->getMessage()}");
        }
    }

    /**
     * Check if there's a next page
     */
    protected function hasNextPage(Page $page): bool
    {
        try {
            return ! ($page->evaluate("document.querySelector('.pagination-container > button:last-child')?.disabled")->getReturnValue() ?? true);
        } catch (\Exception $e) {
            report("Error checking for next page: {$e->getMessage()}");

            return false;
        }
    }
}
