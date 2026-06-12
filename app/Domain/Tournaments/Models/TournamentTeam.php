<?php

namespace App\Domain\Tournaments\Models;

use App\Domain\Tournaments\Enums\TeamType;
use Database\Factories\TournamentTeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tournament_id
 * @property string $name
 * @property string|null $short_name
 * @property TeamType $type
 * @property string|null $external_ref
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Tournament $tournament
 * @property-read Collection<int, Player> $players
 * @property-read Collection<int, TournamentMatch> $homeMatches
 * @property-read Collection<int, TournamentMatch> $awayMatches
 */
#[Fillable(['tournament_id', 'name', 'short_name', 'type', 'external_ref', 'metadata'])]
class TournamentTeam extends Model
{
    /** @use HasFactory<TournamentTeamFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tournament_teams';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): TournamentTeamFactory
    {
        return TournamentTeamFactory::new();
    }

    /**
     * Get the tournament that owns this competing team.
     *
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class, 'tournament_id');
    }

    /**
     * Get all players that belong to this competing team.
     *
     * @return HasMany<Player, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'tournament_team_id');
    }

    /**
     * Get all matches where this team is the home team.
     *
     * @return HasMany<TournamentMatch, $this>
     */
    public function homeMatches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'home_tournament_team_id');
    }

    /**
     * Get all matches where this team is the away team.
     *
     * @return HasMany<TournamentMatch, $this>
     */
    public function awayMatches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'away_tournament_team_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TeamType::class,
            'metadata' => 'array',
        ];
    }
}
