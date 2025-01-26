<?php

namespace App\Actions;

use App\Actions\Concerns\PrintsPrettyJson;
use App\DTO\Items\BaseItem;
use App\Enums\Status;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class ScrapeSubitoPrices
{
    use AsAction, PrintsPrettyJson;

    public string $commandSignature = 'scrape:prices {url} {--head}';

    protected bool $headless = true;

    public function handle(string $url)
    {
        $browserFactory = new BrowserFactory;

        $innerHeight = 1080;

        $browser = $browserFactory->createBrowser([
            'headless' => $this->headless,
            'windowSize' => [1920, $innerHeight],
        ]);

        try {
            $page = $browser->createPage();
            $page->navigate($url)->waitForNavigation();

            // Accept Cookie Banner
            $page->evaluate("document.querySelector('.didomi-continue-without-agreeing').click()");

            $items = $this->getEmptyItems($page);

            /** @var int $pageHeight */
            $pageHeight = $page->evaluate('document.body.scrollHeight')->getReturnValue();

            // TODO: Implement pagination

            for ($i = 1; $i <= ceil($pageHeight / $innerHeight); $i++) {
                $currentHeight = $innerHeight * $i;
                $page->evaluate("window.scrollTo(0, $currentHeight)");

                $data = [
                    'titles' => $this->getItemTitles($page),
                    'prices' => $this->getItemPrices($page),
                    'towns' => $this->getItemTowns($page),
                    'uploadedTimes' => $this->getItemUploadedTimes($page),
                    'status' => $this->getItemStatus($page),
                ];

                $items = $items->map(
                    function (?BaseItem $item, int $key) use ($data) {
                        if ($item) {
                            return $item;
                        }

                        $title = $data['titles']->get($key);
                        $price = $data['prices']->get($key);
                        $town = $data['towns']->get($key);
                        $uploadedTime = $data['uploadedTimes']->get($key);
                        $status = $data['status']->get($key);

                        if ($title && $price && $town && $uploadedTime) {
                            return new BaseItem(
                                title: $title,
                                price: $price,
                                town: $town,
                                uploadedDateTime: $uploadedTime,
                                status: match ($status) {
                                    'Usato' => Status::USED,
                                    'Nuovo' => Status::NEW,
                                    default => null,
                                }
                            );
                        }

                        return null;
                    }
                );

                usleep(0.1 * 100000);
            }

            return $items;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        } finally {
            $browser->close();
        }
    }

    public function asCommand(Command $command): void
    {
        $this->headless = ! $command->option('head');

        $data = $this->handle($command->argument('url'));

        $this->printPrettyJson($data, $command);
    }

    private function getEmptyItems(Page $page): Collection
    {
        return collect($page
            ->evaluate("[...document.querySelectorAll('.items__item.item-card')].map(item => null)")
            ->getReturnValue());
    }

    private function getItemTitles(Page $page): Collection
    {
        return collect($page
            ->evaluate("[...document.querySelectorAll('.items__item.item-card')]
                .map(item => item.querySelector('[class*=\"SmallCard-module_item-title\"]')?.innerText)")
            ->getReturnValue())
            ->map(fn ($value) => ! $value ? null : $value);
    }

    private function getItemPrices(Page $page): Collection
    {
        return collect($page
            ->evaluate("[...document.querySelectorAll('.items__item.item-card')]
                .map(item => item.querySelector('[class*=\"SmallCard-module_price\"]')?.innerText)")
            ->getReturnValue())
            ->map(fn (?string $value) => $value ? (int) $value : null);
    }

    private function getItemTowns(Page $page): Collection
    {
        return collect($page
            ->evaluate("[...document.querySelectorAll('.items__item.item-card')]
                .map(item => item.querySelector('[class*=\"index-module_town\"]')?.innerText)")
            ->getReturnValue())
            ->map(fn ($value) => ! $value ? null : $value);
    }

    private function getItemUploadedTimes(Page $page): Collection
    {
        return collect($page
            ->evaluate("[...document.querySelectorAll('.items__item.item-card')]
                .map(item => item.querySelector('[class*=\"index-module_date\"]')?.innerText)")
            ->getReturnValue())
            ->map(function ($value) {
                if (! $value) {
                    return null;
                }

                $value = str($value)
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

    private function getItemStatus(Page $page): Collection
    {
        return collect($page
            ->evaluate("[...document.querySelectorAll('.items__item.item-card')]
                .map(item => item.querySelectorAll('[class*=\"index-module_info\"]')[0]?.innerText)")
            ->getReturnValue())
            ->map(fn ($value) => ! $value ? null : $value);
    }
}
