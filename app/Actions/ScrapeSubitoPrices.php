<?php

namespace App\Actions;

use App\Actions\Concerns\PrintsPrettyJson;
use HeadlessChromium\BrowserFactory;
use Illuminate\Console\Command;
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
            //'keepAlive' => true,
            'windowSize' => [1920, $innerHeight],
        ]);

        try {
            $page = $browser->createPage();
            $page->navigate($url)->waitForNavigation();

            // Accept Cookie Banner
            sleep(0.5);
            $page->evaluate("document.querySelector('.didomi-continue-without-agreeing').click()");

            $data = collect($page
                ->evaluate($this->getItemsScript())
                ->getReturnValue());

            /** @var int $pageHeight */
            $pageHeight = $page->evaluate('document.body.scrollHeight')->getReturnValue();

            for ($i = 1; $i <= ceil($pageHeight / $innerHeight); $i++) {
                $currentHeight = $innerHeight * $i;
                $page->evaluate("window.scrollTo(0, $currentHeight)");

                $items = collect($page
                    ->evaluate($this->getItemsScript())
                    ->getReturnValue());

                $data = $data->map(
                    fn (string $item, int $key) => ! $item ? $items->get($key) : $item
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
        $this->printPrettyJson($data, $command);
    }

    private function getItemsScript(): string
    {
        return "[...document.querySelectorAll('.items__item.item-card')].map(item => item.querySelector('[class*=\"SmallCard-module_item-title\"]').innerText)";
    }
}
