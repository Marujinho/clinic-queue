<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketStatus;
use Database\Factories\QueueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'priority_enabled', 'active', 'department_id'])]
class Queue extends Model
{
    /** @use HasFactory<QueueFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority_enabled' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<QueueTicket, $this>
     */
    public function queueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function waitingCount(): int
    {
        return $this->queueTickets()->where('status', TicketStatus::Waiting)->count();
    }

    /**
     * @param  Builder<Queue>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }
}
