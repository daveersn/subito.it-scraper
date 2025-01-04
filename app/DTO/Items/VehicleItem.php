<?php

namespace App\DTO\Items;

use App\Enums\Status;
use DateTimeInterface;

class VehicleItem extends BaseItem
{
    public function __construct(
        string $title,
        int $price,
        string $town,
        DateTimeInterface $uploadedDateTime,
        ?Status $status,
        public ?string $registrationYear,
        public ?string $km,
        public ?string $cc,
    ) {
        parent::__construct($title, $price, $town, $uploadedDateTime, $status);
    }
}
