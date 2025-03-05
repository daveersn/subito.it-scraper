<?php

use App\Actions\DispatchScheduledTrackedSearches;
use App\Actions\Scraper\FillTrackedSearchTitle;
use App\Actions\Scraper\ScrapeSubitoPage;
use App\Actions\TrackSearch;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withCommands([
        ScrapeSubitoPage::class,
        TrackSearch::class,
        FillTrackedSearchTitle::class,
        DispatchScheduledTrackedSearches::class,
    ])
    ->create();
