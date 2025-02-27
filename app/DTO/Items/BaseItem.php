<?php

namespace App\DTO\Items;

use App\Enums\ItemStatus;
use Cknow\Money\Money;
use DateTimeInterface;
use Spatie\LaravelData\Data;

class BaseItem extends Data
{
    public function __construct(
        public int $item_id,
        public string $title,
        public Money $price,
        public string $town,
        public DateTimeInterface $uploadedDateTime,
        public ?ItemStatus $status,
        public string $link
    ) {}
}
