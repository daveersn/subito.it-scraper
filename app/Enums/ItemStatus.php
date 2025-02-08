<?php

namespace App\Enums;

enum ItemStatus: string
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
