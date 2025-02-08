<?php

namespace App\Filament\Resources\TrackedSearchResource\Pages;

use App\Filament\Resources\TrackedSearchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrackedSearches extends ListRecords
{
    protected static string $resource = TrackedSearchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data) {
                    $data['user_id'] = auth()->id();

                    return $data;
                }),
        ];
    }
}
