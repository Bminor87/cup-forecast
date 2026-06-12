<?php

namespace App\Domain\Tournaments\Models;

use App\Domain\Tournaments\Enums\PredictionResultStatus;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Models\User;
use Database\Factories\PredictionResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

#[Fillable([
    'tournament_id',
    'prediction_field_id',
    'tournament_match_id',
    'context_key',
    'value',
    'status',
    'resolved_by',
    'resolved_at',
])]
class PredictionResult extends Model
{
    /** @use HasFactory<PredictionResultFactory> */
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PredictionResultFactory
    {
        return PredictionResultFactory::new();
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (PredictionResult $result): void {
            if ($result->predictionField === null) {
                return;
            }

            $result->tournament_id = $result->predictionField->tournament_id;

            if ($result->predictionField->scope === PredictionScope::Match) {
                if ($result->tournament_match_id === null) {
                    throw new InvalidArgumentException('Match scoped results require a match context.');
                }

                $matchTournamentId = TournamentMatch::query()
                    ->whereKey($result->tournament_match_id)
                    ->value('tournament_id');

                if ($matchTournamentId !== $result->tournament_id) {
                    throw new InvalidArgumentException('Result match context must belong to the same tournament as the field.');
                }

                $result->context_key = Prediction::contextKeyForMatch($result->tournament_match_id);
            } else {
                if ($result->tournament_match_id !== null) {
                    throw new InvalidArgumentException('Tournament scoped results cannot have a match context.');
                }

                $result->context_key = Prediction::tournamentContextKey();
            }
        });
    }

    /**
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class, 'tournament_id');
    }

    /**
     * @return BelongsTo<PredictionField, $this>
     */
    public function predictionField(): BelongsTo
    {
        return $this->belongsTo(PredictionField::class, 'prediction_field_id');
    }

    /**
     * @return BelongsTo<TournamentMatch, $this>
     */
    public function tournamentMatch(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'tournament_match_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tournament_match_id' => 'integer',
            'value' => 'array',
            'status' => PredictionResultStatus::class,
            'resolved_at' => 'datetime',
        ];
    }
}
