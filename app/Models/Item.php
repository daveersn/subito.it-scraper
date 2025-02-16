<?php

namespace App\Models;

use App\Enums\ItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'uploadedDateTime' => 'datetime',
            'status' => ItemStatus::class,
        ];
    }
}
