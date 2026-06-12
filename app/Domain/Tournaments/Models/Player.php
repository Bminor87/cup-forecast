<?php

namespace App\Domain\Tournaments\Models;

use App\Domain\Tournaments\Enums\PlayerPosition;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tournament_id
 * @property int $tournament_team_id
 * @property string $name
 * @property string|null $short_name
 * @property int|null $shirt_number
 * @property PlayerPosition|null $position
 * @property string|null $external_ref
 * @property string|null $image_url
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Tournament $tournament
 * @property-read TournamentTeam $tournamentTeam
 */
#[Fillable([
    'tournament_id',
    'tournament_team_id',
    'name',
    'short_name',
    'shirt_number',
    'position',
    'external_ref',
    'image_url',
    'metadata',
])]
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'players';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PlayerFactory
    {
        return PlayerFactory::new();
    }

    /**
     * Get the tournament that the player belongs to.
     *
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class, 'tournament_id');
    }

    /**
     * Get the competing team that the player belongs to.
     *
     * @return BelongsTo<TournamentTeam, $this>
     */
    public function tournamentTeam(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'tournament_team_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => PlayerPosition::class,
            'metadata' => 'array',
        ];
    }
}
