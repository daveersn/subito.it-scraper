<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ItemStatus: string implements HasColor, HasLabel
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

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::USED => Color::Blue,
            self::NEW => Color::Green,
        };
    }
}
