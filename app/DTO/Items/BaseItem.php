<?php

namespace App\DTO\Items;

use App\Enums\ItemStatus;
use DateTimeInterface;
use Spatie\LaravelData\Data;

class BaseItem extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public int $price,
        public string $town,
        public DateTimeInterface $uploadedDateTime,
        public ?ItemStatus $status,
        public string $link
    ) {}
}
