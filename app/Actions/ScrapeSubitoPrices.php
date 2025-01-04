<?php

namespace App\Actions;

use App\Actions\Concerns\PrintsPrettyJson;
use App\DTO\Items\BaseItem;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class ScrapeSubitoPrices
{
    use AsAction, PrintsPrettyJson;

    public string $commandSignature = 'scrape:prices {url}';

    public function handle(string $url)
    {
        $browserFactory = new BrowserFactory;

        $innerHeight = 1080;

        $browser = $browserFactory->createBrowser([
            'headless' => false,
            // 'keepAlive' => true,
            'windowSize' => [1920, $innerHeight],
        ]);

        try {
            $page = $browser->createPage();
            $page->navigate($url)->waitForNavigation();

            // Accept Cookie Banner
            sleep(0.5);
            $page->evaluate("document.querySelector('.didomi-continue-without-agreeing').click()");

            $data = $this->getEmptyItems($page);

            /** @var int $pageHeight */
            $pageHeight = $page->evaluate('document.body.scrollHeight')->getReturnValue();

            for ($i = 1; $i <= ceil($pageHeight / $innerHeight); $i++) {
                $currentHeight = $innerHeight * $i;
                $page->evaluate("window.scrollTo(0, $currentHeight)");

                $titles = $this->getItemTitles($page);
                $prices = $this->getItemPrices($page);
                $towns = $this->getItemTowns($page);
                $uploadedTimes = $this->getItemUploadedTimes($page);

                $iterations = max([
                    $titles->filter()->count(),
                    $prices->filter()->count(),
                    $towns->filter()->count(),
                    $uploadedTimes->filter()->count(),
                ]);

                $data = $data->map(
                    fn (?BaseItem $item, int $key) => ! $item
                        ? new BaseItem(
                            title: $titles->get($key),
                            price: $prices->get($key),
                            town: $towns->get($key),
                            uploadedDateTime: $uploadedTimes->get($key),
                        )
                        : $item
                );

                sleep(0.3);
            }

            return $data;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        } finally {
            $browser->close();
        }
    }

    public function asCommand(Command $command): void
    {
        $data = $this->handle($command->argument('url'));
        dump($data);
        // $this->printPrettyJson($data, $command);
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
}
