<?php

namespace Database\Factories;

use App\Domain\Tournaments\Enums\PredictionStatus;
use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prediction>
 */
class PredictionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Prediction>
     */
    protected $model = Prediction::class;

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
            'user_id' => User::factory(),
            'tournament_match_id' => null,
            'context_key' => Prediction::tournamentContextKey(),
            'value' => ['value' => fake()->word()],
            'status' => PredictionStatus::Submitted,
            'submitted_at' => now(),
            'locked_at' => null,
        ];
    }

    public function forField(PredictionField $field): static
    {
        return $this->state(fn (): array => [
            'tournament_id' => $field->tournament_id,
            'prediction_field_id' => $field->id,
        ]);
    }

    public function forMatch(TournamentMatch $match): static
    {
        return $this->state(fn (): array => [
            'tournament_match_id' => $match->id,
            'context_key' => Prediction::contextKeyForMatch($match->id),
        ]);
    }
}
