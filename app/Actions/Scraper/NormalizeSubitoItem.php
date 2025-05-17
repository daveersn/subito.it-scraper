<?php

namespace App\Actions\Scraper;

use App\DTO\Items\BaseItem;
use App\Enums\ItemStatus;
use Cknow\Money\Money;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class NormalizeSubitoItem
{
    use AsAction;

    /**
     * Transform raw JavaScript data into BaseItem DTOs
     */
    public function handle(Collection $rawItems): Collection
    {
        return $rawItems->map(function (array $rawItem) {
            try {
                // Extract ID from href (the final numeric segment before .htm)
                $id = (int) str($rawItem['href'])
                    ->afterLast('-')
                    ->beforeLast('.')
                    ->toString();

                // Parse price string to Money object
                $price = Money::parse(str_replace('.', '', $rawItem['price']));

                // Parse uploaded time string to DateTime
                $uploadedTime = str($rawItem['uploaded'])
                    ->replace('Oggi', now()->format('j M'))
                    ->replace('Ieri', now()->subDay()->format('j M'))
                    ->replace(' alle', '')
                    ->replace(
                        [
                            'gen', 'feb', 'mar', 'apr', 'mag', 'giu',
                            'lug', 'ago', 'set', 'ott', 'nov', 'dic',
                        ],
                        [
                            'jan', 'feb', 'mar', 'apr', 'may', 'jun',
                            'jul', 'aug', 'sep', 'oct', 'nov', 'dec',
                        ],
                        false
                    );

                $uploadedDateTime = \Carbon\Carbon::createFromFormat('j M H:i', $uploadedTime, 'Europe/Rome');

                // Convert status string to ItemStatus enum
                $status = match ($rawItem['status']) {
                    'Usato' => ItemStatus::USED,
                    'Nuovo' => ItemStatus::NEW,
                    default => null,
                };

                return new BaseItem(
                    item_id: $id,
                    title: $rawItem['title'],
                    price: $price,
                    town: $rawItem['town'],
                    uploadedDateTime: $uploadedDateTime,
                    status: $status,
                    link: $rawItem['href']
                );
            } catch (\Exception $e) {
                report("Failed to normalize Subito item: {$e->getMessage()}");

                return null;
            }
        })->filter();
    }
}
