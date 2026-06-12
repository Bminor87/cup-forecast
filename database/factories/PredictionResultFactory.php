<?php

namespace Database\Factories;

use App\Domain\Tournaments\Enums\PredictionResultStatus;
use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PredictionResult>
 */
class PredictionResultFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PredictionResult>
     */
    protected $model = PredictionResult::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $field = PredictionField::factory()->create();

        return [
            'tournament_id' => $field->tournament_id,
            'prediction_field_id' => $field->id,
            'tournament_match_id' => null,
            'context_key' => Prediction::tournamentContextKey(),
            'value' => ['value' => fake()->word()],
            'status' => PredictionResultStatus::Pending,
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }

    public function resolved(?User $resolver = null): static
    {
        $resolver ??= User::factory()->create();

        return $this->state(fn (): array => [
            'status' => PredictionResultStatus::Resolved,
            'resolved_by' => $resolver->id,
            'resolved_at' => now(),
        ]);
    }

    public function forMatch(TournamentMatch $match): static
    {
        return $this->state(fn (): array => [
            'tournament_id' => $match->tournament_id,
            'tournament_match_id' => $match->id,
            'context_key' => Prediction::contextKeyForMatch($match->id),
        ]);
    }
}
