<?php

use App\Actions\Chrome\KillSockets;
use App\Actions\DispatchScheduledTrackedSearches;

Schedule::call(function () {
    DispatchScheduledTrackedSearches::run();
})->dailyAt('00:30');

Schedule::call(function () {
    KillSockets::run();
})->dailyAt('4:00');
