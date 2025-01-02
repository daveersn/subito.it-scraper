<?php

namespace App\DTO;

use App\Enums\Status;
use DateTimeInterface;

class SubitoItem
{
    public function __construct(
        public string $title,
        public int $price,
        public string $place,
        public DateTimeInterface $uploadedDateTime,
        public ?Status $status,
        public ?string $registrationYear,
        public ?string $km,
        public ?string $cc,
    ) {}
}
