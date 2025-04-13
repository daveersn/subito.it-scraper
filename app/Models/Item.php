<?php

namespace App\Models;

use App\Enums\ItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'uploadedDateTime' => 'datetime',
            'status' => ItemStatus::class,
        ];
    }

    protected $with = ['price'];

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    public function price(): HasOne
    {
        return $this->hasOne(Price::class)->latestOfMany();
    }

    public function trackedSearch(): BelongsTo
    {
        return $this->belongsTo(TrackedSearch::class);
    }
}
