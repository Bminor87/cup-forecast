<?php

namespace App\Domain\Tournaments\Models;

use App\Domain\Tournaments\Enums\TeamType;
use Database\Factories\TournamentTeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
