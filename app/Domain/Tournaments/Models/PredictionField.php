<?php

namespace App\Domain\Tournaments\Models;

use App\Domain\Tournaments\Enums\PredictionFieldType;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Enums\PredictionVisibility;
use Database\Factories\PredictionFieldFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tournament_id',
    'scope',
    'field_type',
    'label',
    'description',
    'key',
    'visibility',
    'validation_schema',
    'scoring_strategy_key',
    'configuration',
    'is_active',
])]
class PredictionField extends Model
{
    /** @use HasFactory<PredictionFieldFactory> */
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PredictionFieldFactory
    {
        return PredictionFieldFactory::new();
    }

    /**
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class, 'tournament_id');
    }

    /**
     * @return HasMany<Prediction, $this>
     */
    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class, 'prediction_field_id');
    }

    /**
     * @return HasMany<PredictionResult, $this>
     */
    public function predictionResults(): HasMany
    {
        return $this->hasMany(PredictionResult::class, 'prediction_field_id');
    }

    public function isManuallyLocked(): bool
    {
        return (bool) ($this->configuration['is_locked'] ?? false);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => PredictionScope::class,
            'field_type' => PredictionFieldType::class,
            'visibility' => PredictionVisibility::class,
            'validation_schema' => 'array',
            'configuration' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
