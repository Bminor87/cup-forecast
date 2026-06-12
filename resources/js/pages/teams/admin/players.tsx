import { Form, Head, router } from '@inertiajs/react';
import { Shirt, UserRoundPlus, X } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TournamentAdminNav from '@/components/teams/tournament-admin-nav';
import { Badge } from '@/components/ui/badge';
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
                <section className="rounded-2xl border border-sky-900/50 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 p-6">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <Heading
                            variant="small"
                            title="Squad Desk"
                            description="Register player pools so match MVP, scorer, and award predictions stay accurate."
                        />

                        <Badge variant="outline" className="border-sky-700/70 bg-sky-500/10 text-sky-200">
                            <Shirt className="mr-1 h-3 w-3" />
                            {players.length} players
                        </Badge>
                    </div>
                </section>

                <TournamentAdminNav teamSlug={teamSlug} />

                {canManagePlayers ? (
                    <Form
                        action={`/${teamSlug}/admin/players`}
                        method="post"
                        className="grid gap-4 rounded-xl border border-slate-800 bg-slate-950/60 p-5 md:grid-cols-3"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="md:col-span-3 flex items-center gap-2 text-sm text-slate-200">
                                    <UserRoundPlus className="h-4 w-4" />
                                    Add a player to a tournament squad
                                </div>

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
                                        Add to squad
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                ) : null}

                <div className="grid gap-3 md:grid-cols-2">
                    {players.map((player) => (
                        <div key={player.id} className="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-full border border-slate-700 bg-slate-900 text-slate-100">
                                    {player.shirt_number ?? '-'}
                                </div>

                                <div>
                                    <div className="font-medium text-slate-100">{player.name}</div>
                                    <div className="text-sm text-slate-400">
                                    {player.team_name}
                                    {player.position ? ` • ${player.position}` : ''}
                                    {player.shirt_number ? ` • #${player.shirt_number}` : ''}
                                    </div>
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
