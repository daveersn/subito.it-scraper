<?php

namespace App\DTO\Items;

use App\Enums\ItemStatus;
use DateTimeInterface;

class VehicleItem extends BaseItem
{
    public function __construct(
        string $title,
        int $price,
        string $town,
        DateTimeInterface $uploadedDateTime,
        ?ItemStatus $status,
        public ?string $registrationYear,
        public ?string $km,
        public ?string $cc,
    ) {
        parent::__construct($title, $price, $town, $uploadedDateTime, $status);
    }
}
