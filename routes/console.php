<?php

use App\Actions\DispatchScheduledTrackedSearches;

Schedule::call(function () {
    DispatchScheduledTrackedSearches::run();
})->daily();
