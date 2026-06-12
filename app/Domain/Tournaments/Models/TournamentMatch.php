<?php

namespace App\Domain\Tournaments\Models;

use App\Domain\Tournaments\Enums\MatchStatus;
use Database\Factories\TournamentMatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * @property int $id
 * @property int $tournament_id
 * @property int $home_tournament_team_id
 * @property int $away_tournament_team_id
 * @property Carbon $starts_at
 * @property Carbon|null $locks_at
 * @property MatchStatus $status
 * @property string|null $venue
 * @property int|null $home_score
 * @property int|null $away_score
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Tournament $tournament
 * @property-read TournamentTeam $homeTournamentTeam
 * @property-read TournamentTeam $awayTournamentTeam
 */
#[Fillable([
    'tournament_id',
    'home_tournament_team_id',
    'away_tournament_team_id',
    'starts_at',
    'locks_at',
    'status',
    'venue',
    'home_score',
    'away_score',
    'metadata',
])]
class TournamentMatch extends Model
{
    /** @use HasFactory<TournamentMatchFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'matches';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): TournamentMatchFactory
    {
        return TournamentMatchFactory::new();
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (TournamentMatch $match): void {
            $match->ensureTeamIntegrity();
        });
    }

    /**
     * Get the tournament this match belongs to.
     *
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class, 'tournament_id');
    }

    /**
     * Get the home team for the match.
     *
     * @return BelongsTo<TournamentTeam, $this>
     */
    public function homeTournamentTeam(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'home_tournament_team_id');
    }

    /**
     * Get the away team for the match.
     *
     * @return BelongsTo<TournamentTeam, $this>
     */
    public function awayTournamentTeam(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'away_tournament_team_id');
    }

    /**
     * Validate that home/away teams are valid for this tournament.
     */
    public function ensureTeamIntegrity(): void
    {
        if (! $this->tournament_id) {
            throw new InvalidArgumentException('Match must belong to a tournament.');
        }

        if ($this->home_tournament_team_id === $this->away_tournament_team_id) {
            throw new InvalidArgumentException('Home and away teams must be different.');
        }

        $homeTeamTournamentId = TournamentTeam::query()
            ->whereKey($this->home_tournament_team_id)
            ->value('tournament_id');

        $awayTeamTournamentId = TournamentTeam::query()
            ->whereKey($this->away_tournament_team_id)
            ->value('tournament_id');

        if ($homeTeamTournamentId === null || $awayTeamTournamentId === null) {
            throw new InvalidArgumentException('Home and away teams must exist.');
        }

        if ($homeTeamTournamentId !== $this->tournament_id || $awayTeamTournamentId !== $this->tournament_id) {
            throw new InvalidArgumentException('Home and away teams must belong to the same tournament as the match.');
        }
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'locks_at' => 'datetime',
            'status' => MatchStatus::class,
            'metadata' => 'array',
        ];
    }
}
