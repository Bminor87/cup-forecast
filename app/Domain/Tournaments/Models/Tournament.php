<?php

namespace App\Domain\Tournaments\Models;

use App\Concerns\GeneratesUniqueTeamSlugs;
use App\Domain\Tournaments\Enums\TournamentCompetitionMode;
use App\Domain\Tournaments\Enums\TournamentSportType;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_personal
 * @property TournamentSportType|null $sport_type
 * @property TournamentCompetitionMode|null $competition_mode
 * @property TournamentStatus|null $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string $timezone
 * @property string|null $scoring_strategy_key
 * @property int|null $scoring_strategy_version
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TeamInvitation> $invitations
 * @property-read Collection<int, Membership> $participantMemberships
 * @property-read Collection<int, User> $participants
 * @property-read Collection<int, TournamentTeam> $tournamentTeams
 * @property-read Collection<int, Player> $players
 */
#[Fillable([
    'name',
    'slug',
    'is_personal',
    'sport_type',
    'competition_mode',
    'status',
    'starts_at',
    'ends_at',
    'timezone',
    'scoring_strategy_key',
    'scoring_strategy_version',
    'settings',
    'archived_at',
])]
class Tournament extends Model
{
    use GeneratesUniqueTeamSlugs, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'teams';

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Tournament $tournament): void {
            if (empty($tournament->slug)) {
                $tournament->slug = static::generateUniqueTeamSlug($tournament->name);
            }

            $tournament->is_personal = false;
        });

        static::updating(function (Tournament $tournament): void {
            if ($tournament->isDirty('name')) {
                $tournament->slug = static::generateUniqueTeamSlug($tournament->name, $tournament->id);
            }
        });
    }

    /**
     * Get all users participating in the tournament.
     *
     * @return BelongsToMany<User, $this, Membership, 'pivot'>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->using(Membership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all participant membership records for this tournament.
     *
     * @return HasMany<Membership, $this>
     */
    public function participantMemberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'team_id');
    }

    /**
     * Get all invitations for this tournament.
     *
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class, 'team_id');
    }

    /**
     * Get all competing teams in the tournament.
     *
     * @return HasMany<TournamentTeam, $this>
     */
    public function tournamentTeams(): HasMany
    {
        return $this->hasMany(TournamentTeam::class, 'tournament_id');
    }

    /**
     * Get all players in the tournament.
     *
     * @return HasMany<Player, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'tournament_id');
    }

    /**
     * Get the tournament owner.
     */
    public function owner(): ?User
    {
        /** @var User|null $owner */
        $owner = $this->participants()
            ->wherePivot('role', TeamRole::Owner->value)
            ->first();

        return $owner;
    }

    /**
     * Determine if the tournament is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === TournamentStatus::Archived;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_personal' => 'boolean',
            'sport_type' => TournamentSportType::class,
            'competition_mode' => TournamentCompetitionMode::class,
            'status' => TournamentStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'settings' => 'array',
            'archived_at' => 'datetime',
        ];
    }
}
