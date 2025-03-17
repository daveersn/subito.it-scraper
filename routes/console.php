<?php

use App\Actions\Chrome\GetSockets;
use App\Actions\Chrome\KillSockets;
use App\Actions\DispatchScheduledTrackedSearches;

Schedule::call(function () {
    DispatchScheduledTrackedSearches::run();
})->dailyAt('00:30');

Schedule::call(function () {
    if (count(GetSockets::run())) {
        KillSockets::run();
    }
});
