<?php

namespace App\Actions\Scraper;

use App\Actions\Concerns\PrintsPrettyJson;
use App\DTO\Items\BaseItem;
use App\Enums\ItemStatus;
use App\Scraper\Scraper;
use Cknow\Money\Money;
use HeadlessChromium\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class ScrapeSubitoPage
{
    use AsAction, PrintsPrettyJson;

    public string $commandSignature = 'scrape:prices {url} {--head}';

    protected bool $headless = true;

    protected Page $page;

    public function handle(Page $page, string $url)
    {
        $this->page = $page;

        try {
            $this->page->navigate($url)->waitForNavigation();

            // Accept Cookie Banner
            $this->page->evaluate("document.querySelector('.didomi-continue-without-agreeing').click()");

            // Create empty items collection, to be filled later
            $items = $this->getEmptyItems();

            /** @var int $pageHeight */
            $pageHeight = $this->page->evaluate('document.body.scrollHeight')->getReturnValue();
            $innerHeight = $this->page->evaluate('window.innerHeight')->getReturnValue();

            // TODO: Implement pagination

            // Scroll page n times browser inner height, so item infos are retrieved correctly
            for ($i = 1; $i <= ceil($pageHeight / $innerHeight); $i++) {
                $currentHeight = $innerHeight * $i;
                $this->page->evaluate("window.scrollTo(0, $currentHeight)");

                $data = [
                    'titles' => $this->getItemTitles(),
                    'prices' => $this->getItemPrices(),
                    'towns' => $this->getItemTowns(),
                    'uploadedTimes' => $this->getItemUploadedTimes(),
                    'status' => $this->getItemStatus(),
                    'link' => $this->getItemHref(),
                ];

                $items = $items->map(
                    function (?BaseItem $item, int $key) use ($data) {
                        // If item is already filled, do nothing
                        if ($item) {
                            return $item;
                        }

                        $title = $data['titles']->get($key);
                        $price = $data['prices']->get($key);
                        $town = $data['towns']->get($key);
                        $uploadedTime = $data['uploadedTimes']->get($key);
                        $status = match ($data['status']->get($key)) {
                            'Usato' => ItemStatus::USED,
                            'Nuovo' => ItemStatus::NEW,
                            default => null,
                        };
                        $link = $data['link']->get($key);
                        $id = str($link)->afterLast('-')->beforeLast('.')->toInteger();

                        // Fill item only if all required properties are filled
                        if (! $title || ! $price || ! $town || ! $uploadedTime) {
                            return null;
                        }

                        // Create and fill item DTO to easily manage it later
                        return new BaseItem(
                            item_id: $id,
                            title: $title,
                            price: $price,
                            town: $town,
                            uploadedDateTime: $uploadedTime,
                            status: $status,
                            link: $link
                        );
                    }
                );

                usleep(0.1 * 100000);
            }

            return $items->filter();
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
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

    private function getEmptyItems(): Collection
    {
        return collect($this->page
            ->evaluate("[...document.querySelectorAll('.items__item.item-card')].map(item => null)")
            ->getReturnValue());
    }

    private function getItemTitles(): Collection
    {
        return collect($this->page
            ->evaluate("[...document.querySelectorAll('.items__item.item-card')]
                .map(item => item.querySelector('[class*=\"SmallCard-module_item-title\"]')?.innerText)")
            ->getReturnValue())
            ->map(fn ($value) => ! $value ? null : trim($value));
    }

    private function getItemPrices(): Collection
    {
        return collect($this->page
            ->evaluate("[...document.querySelectorAll('.items__item.item-card')]
                .map(item => item.querySelector('[class*=\"SmallCard-module_price\"]')?.innerText)")
            ->getReturnValue())
            ->map(fn (?string $value) => $value
                ? Money::parse(Str::replace('.', '', $value))
                : null
            );
    }

    private function getItemTowns(): Collection
    {
        return collect($this->page
            ->evaluate("[...document.querySelectorAll('.items__item.item-card')]
                .map(item => item.querySelector('[class*=\"index-module_town\"]')?.innerText)")
            ->getReturnValue())
            ->map(fn ($value) => ! $value ? null : trim($value));
    }

    private function getItemUploadedTimes(): Collection
    {
        return collect($this->page
            ->evaluate("[...document.querySelectorAll('.items__item.item-card')]
                .map(item => item.querySelector('[class*=\"index-module_date\"]')?.innerText)")
            ->getReturnValue())
            ->map(function ($value) {
                if (! $value) {
                    return null;
                }

                $value = str($value)
                    ->replace('Oggi', now()->format('j M'))
                    ->replace('Ieri', now()->subDay()->format('j M'))
                    ->replace(' alle', '')
                    ->replace(
                        [
                            'gen',
                            'feb',
                            'mar',
                            'apr',
                            'mag',
                            'giu',
                            'lug',
                            'ago',
                            'set',
                            'ott',
                            'nov',
                            'dic',
                        ],
                        [
                            'jan',
                            'feb',
                            'mar',
                            'apr',
                            'may',
                            'jun',
                            'jul',
                            'aug',
                            'sep',
                            'oct',
                            'nov',
                            'dec',
                        ],
                        false
                    );

                return Carbon::createFromFormat('j M H:i', $value, 'Europe/Rome');
            });
    }

    private function getItemStatus(): Collection
    {
        return collect($this->page
            ->evaluate("[...document.querySelectorAll('.items__item.item-card')]
                .map(item => item.querySelectorAll('[class*=\"index-module_info\"]')[0]?.innerText)")
            ->getReturnValue())
            ->map(fn ($value) => ! $value ? null : trim($value));
    }

    private function getItemHref(): Collection
    {
        return collect($this->page
            ->evaluate("[...document.querySelectorAll('.items__item.item-card')]
                .map(item => item.querySelector('a')?.href)")
            ->getReturnValue())
            ->map(fn ($value) => ! $value ? null : trim($value));
    }
}
