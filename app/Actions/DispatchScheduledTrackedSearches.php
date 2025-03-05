<?php

namespace App\Actions;

use App\Models\TrackedSearch;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsObject;

class DispatchScheduledTrackedSearches
{
    use AsCommand, AsObject;

    public string $commandSignature = 'track-search:dispatch-scheduled';

    public function handle(): void
    {
        $searches = TrackedSearch::query()
            ->whereNotNull('schedule')
            ->where('next_scheduled_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('last_schedule_run_at')
                    ->orWhereColumn('last_schedule_run_at', '<', 'next_scheduled_at');
            })
            ->get();

        $searches->each(function (TrackedSearch $trackedSearch) {
            TrackSearch::dispatch($trackedSearch, true);
        });
    }
}
