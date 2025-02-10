<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrowserSocket extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'params' => 'json',
        ];
    }
}
