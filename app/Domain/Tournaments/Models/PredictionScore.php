<?php

namespace App\Domain\Tournaments\Models;

use App\Models\User;
use Database\Factories\PredictionScoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tournament_id',
    'prediction_id',
    'prediction_field_id',
    'user_id',
    'strategy_key',
    'points',
    'max_points',
    'breakdown',
    'scored_at',
])]
class PredictionScore extends Model
{
    /** @use HasFactory<PredictionScoreFactory> */
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PredictionScoreFactory
    {
        return PredictionScoreFactory::new();
    }

    /**
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class, 'tournament_id');
    }

    /**
     * @return BelongsTo<Prediction, $this>
     */
    public function prediction(): BelongsTo
    {
        return $this->belongsTo(Prediction::class, 'prediction_id');
    }

    /**
     * @return BelongsTo<PredictionField, $this>
     */
    public function predictionField(): BelongsTo
    {
        return $this->belongsTo(PredictionField::class, 'prediction_field_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'breakdown' => 'array',
            'scored_at' => 'datetime',
        ];
    }
}
