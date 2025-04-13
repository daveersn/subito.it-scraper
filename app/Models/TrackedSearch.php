<?php

namespace App\Models;

use Cron\CronExpression;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrackedSearch extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'last_run_at' => 'datetime',
            'last_schedule_run_at' => 'datetime',
            'next_scheduled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function prices(): HasManyThrough
    {
        return $this->hasManyThrough(Price::class, Item::class);
    }

    protected function schedule(): Attribute
    {
        return new Attribute(
            get: fn (?string $value) => $value ? new CronExpression($value) : $value,
            set: fn (CronExpression|string|null $value) => [
                'schedule' => $value
                    ? $value instanceof CronExpression ? $value->getExpression() : (new CronExpression($value))->getExpression()
                    : null,
                'next_scheduled_at' => $value
                    ? $value instanceof CronExpression ? $value->getNextRunDate() : (new CronExpression($value))->getNextRunDate()
                    : null,
            ]
        );
    }
}
