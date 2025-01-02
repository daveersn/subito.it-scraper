<?php

namespace App\Crawler;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlObservers\CrawlObserver;

class SubitoPriceObserver extends CrawlObserver
{
    public function willCrawl(UriInterface $url, ?string $linkText): void
    {
        echo "Crawling: $url\n";
    }

    public function crawled(UriInterface $url, ResponseInterface $response, ?UriInterface $foundOnUrl = null, ?string $linkText = null): void
    {
        // Usa il DOM per estrarre i dati
        $html = (string) $response->getBody();

        dd($html);
    }

    public function crawlFailed(UriInterface $url, RequestException $requestException, ?UriInterface $foundOnUrl = null, ?string $linkText = null): void
    {
        dd($requestException->getMessage());
        echo "Crawl failed for URL: $url\n";
    }
}
