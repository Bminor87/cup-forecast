import { Form, Head, router } from '@inertiajs/react';
import { X } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TournamentAdminNav from '@/components/teams/tournament-admin-nav';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type TournamentTeam = {
    id: number;
    name: string;
};

type Player = {
    id: number;
    name: string;
    short_name: string | null;
    shirt_number: number | null;
    position: string | null;
    team_name: string;
};

type Option = {
    value: string;
    label: string;
};

type Props = {
    teamSlug: string;
    tournamentTeams: TournamentTeam[];
    players: Player[];
    positions: Option[];
    canManagePlayers: boolean;
};

export default function AdminPlayersPage({
    teamSlug,
    tournamentTeams,
    players,
    positions,
    canManagePlayers,
}: Props) {
    return (
        <>
            <Head title="Admin Players" />

            <div className="space-y-6 rounded-xl p-4">
                <Heading
                    variant="small"
                    title="Admin: Players"
                    description="Manage players used in player-based prediction templates."
                />

                <TournamentAdminNav teamSlug={teamSlug} />

                {canManagePlayers ? (
                    <Form
                        action={`/${teamSlug}/admin/players`}
                        method="post"
                        className="grid gap-4 rounded-xl border p-4 md:grid-cols-3"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="tournament_team_id">Team</Label>
                                    <select
                                        id="tournament_team_id"
                                        name="tournament_team_id"
                                        className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                        defaultValue={tournamentTeams[0]?.id}
                                    >
                                        {tournamentTeams.map((team) => (
                                            <option key={team.id} value={team.id}>
                                                {team.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.tournament_team_id} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Player name</Label>
                                    <Input id="name" name="name" required />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="short_name">Short name</Label>
                                    <Input id="short_name" name="short_name" />
                                    <InputError message={errors.short_name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="shirt_number">Shirt number</Label>
                                    <Input id="shirt_number" name="shirt_number" type="number" min={1} max={99} />
                                    <InputError message={errors.shirt_number} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="position">Position</Label>
                                    <select
                                        id="position"
                                        name="position"
                                        className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                        defaultValue={positions[0]?.value}
                                    >
                                        {positions.map((position) => (
                                            <option key={position.value} value={position.value}>
                                                {position.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.position} />
                                </div>
                                <div className="md:col-span-3">
                                    <Button type="submit" disabled={processing}>
                                        Add player
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                ) : null}

                <div className="space-y-3">
                    {players.map((player) => (
                        <div key={player.id} className="flex items-center justify-between rounded-xl border p-4">
                            <div>
                                <div className="font-medium">{player.name}</div>
                                <div className="text-sm text-muted-foreground">
                                    {player.team_name}
                                    {player.position ? ` • ${player.position}` : ''}
                                    {player.shirt_number ? ` • #${player.shirt_number}` : ''}
                                </div>
                            </div>

                            {canManagePlayers ? (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => {
                                        router.delete(`/${teamSlug}/admin/players/${player.id}`);
                                    }}
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            ) : null}
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}
