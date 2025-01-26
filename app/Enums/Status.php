<?php

namespace App\Enums;

enum Status: string
{
    case USED = 'U';
    case NEW = 'N';

    public function getLabel(): string
    {
        return match ($this) {
            self::USED => 'Usato',
            self::NEW => 'Nuovo',
        };
    }
}
