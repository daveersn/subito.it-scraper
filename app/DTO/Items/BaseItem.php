<?php

namespace App\DTO\Items;

use App\Enums\Status;
use DateTimeInterface;

class BaseItem
{
    public function __construct(
        public string $title,
        public int $price,
        public string $town,
        public DateTimeInterface $uploadedDateTime,
        public ?Status $status = null,
    ) {}
}
