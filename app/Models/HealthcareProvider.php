<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\HealthcareProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'specialty', 'license_number', 'active'])]
class HealthcareProvider extends Model
{
    /** @use HasFactory<HealthcareProviderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @param  Builder<HealthcareProvider>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }
}
