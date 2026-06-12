<?php

namespace App\Domain\Tournaments\Models;

use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Enums\PredictionStatus;
use App\Models\User;
use Database\Factories\PredictionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use InvalidArgumentException;

#[Fillable([
    'tournament_id',
    'prediction_field_id',
    'user_id',
    'tournament_match_id',
    'context_key',
    'value',
    'status',
    'submitted_at',
    'locked_at',
])]
class Prediction extends Model
{
    /** @use HasFactory<PredictionFactory> */
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PredictionFactory
    {
        return PredictionFactory::new();
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Prediction $prediction): void {
            if ($prediction->predictionField === null) {
                return;
            }

            $prediction->tournament_id = $prediction->predictionField->tournament_id;

            if ($prediction->predictionField->scope === PredictionScope::Match) {
                if ($prediction->tournament_match_id === null) {
                    throw new InvalidArgumentException('Match scoped predictions require a match context.');
                }

                $matchTournamentId = TournamentMatch::query()
                    ->whereKey($prediction->tournament_match_id)
                    ->value('tournament_id');

                if ($matchTournamentId !== $prediction->tournament_id) {
                    throw new InvalidArgumentException('Prediction match context must belong to the same tournament as the field.');
                }

                $prediction->context_key = static::contextKeyForMatch($prediction->tournament_match_id);
            } else {
                if ($prediction->tournament_match_id !== null) {
                    throw new InvalidArgumentException('Tournament scoped predictions cannot have a match context.');
                }

                $prediction->context_key = static::tournamentContextKey();
            }

            if ($prediction->exists) {
                $originalLockedAt = $prediction->getOriginal('locked_at');
                $originalStatus = $prediction->getOriginal('status');

                if ($originalLockedAt !== null || $originalStatus === PredictionStatus::Locked->value) {
                    throw new InvalidArgumentException('Locked predictions cannot be modified.');
                }
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<TournamentMatch, $this>
     */
    public function tournamentMatch(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'tournament_match_id');
    }

    /**
     * @return HasOne<PredictionScore, $this>
     */
    public function score(): HasOne
    {
        return $this->hasOne(PredictionScore::class, 'prediction_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null || $this->status === PredictionStatus::Locked;
    }

    public static function tournamentContextKey(): string
    {
        return 'tournament';
    }

    public static function contextKeyForMatch(int $matchId): string
    {
        return 'match:'.$matchId;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tournament_match_id' => 'integer',
            'value' => 'array',
            'status' => PredictionStatus::class,
            'submitted_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }
}
