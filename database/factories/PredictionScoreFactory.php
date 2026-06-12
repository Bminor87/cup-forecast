<?php

namespace Database\Factories;

use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PredictionScore>
 */
class PredictionScoreFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PredictionScore>
     */
    protected $model = PredictionScore::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prediction = Prediction::factory()->create();

        return [
            'tournament_id' => $prediction->tournament_id,
            'prediction_id' => $prediction->id,
            'prediction_field_id' => $prediction->prediction_field_id,
            'user_id' => $prediction->user_id,
            'strategy_key' => 'exact_match',
            'points' => 0,
            'max_points' => 1,
            'breakdown' => ['matched' => false],
            'scored_at' => now(),
        ];
    }
}
