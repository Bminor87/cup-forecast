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

type TournamentMatch = {
    id: number;
    home_team_name: string;
    away_team_name: string;
    starts_at: string;
    status_label: string;
    venue: string | null;
};

type Option = {
    value: string;
    label: string;
};

type Props = {
    teamSlug: string;
    tournamentTeams: TournamentTeam[];
    matches: TournamentMatch[];
    matchStatuses: Option[];
    canManageMatches: boolean;
};

export default function AdminMatchesPage({
    teamSlug,
    tournamentTeams,
    matches,
    matchStatuses,
    canManageMatches,
}: Props) {
    return (
        <>
            <Head title="Admin Matches" />

            <div className="space-y-6 rounded-xl p-4">
                <Heading
                    variant="small"
                    title="Admin: Matches"
                    description="Create and manage fixtures for match prediction workflows."
                />

                <TournamentAdminNav teamSlug={teamSlug} />

                {canManageMatches && tournamentTeams.length >= 2 ? (
                    <Form
                        action={`/${teamSlug}/admin/matches`}
                        method="post"
                        className="grid gap-4 rounded-xl border p-4 md:grid-cols-3"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="home_tournament_team_id">Home team</Label>
                                    <select
                                        id="home_tournament_team_id"
                                        name="home_tournament_team_id"
                                        className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                        defaultValue={tournamentTeams[0]?.id}
                                    >
                                        {tournamentTeams.map((team) => (
                                            <option key={team.id} value={team.id}>
                                                {team.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.home_tournament_team_id} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="away_tournament_team_id">Away team</Label>
                                    <select
                                        id="away_tournament_team_id"
                                        name="away_tournament_team_id"
                                        className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                        defaultValue={tournamentTeams[1]?.id ?? tournamentTeams[0]?.id}
                                    >
                                        {tournamentTeams.map((team) => (
                                            <option key={team.id} value={team.id}>
                                                {team.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.away_tournament_team_id} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="status">Status</Label>
                                    <select
                                        id="status"
                                        name="status"
                                        className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                        defaultValue={matchStatuses[0]?.value}
                                    >
                                        {matchStatuses.map((status) => (
                                            <option key={status.value} value={status.value}>
                                                {status.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.status} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="starts_at">Starts at</Label>
                                    <Input id="starts_at" name="starts_at" type="datetime-local" required />
                                    <InputError message={errors.starts_at} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="locks_at">Locks at</Label>
                                    <Input id="locks_at" name="locks_at" type="datetime-local" />
                                    <InputError message={errors.locks_at} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="venue">Venue</Label>
                                    <Input id="venue" name="venue" />
                                    <InputError message={errors.venue} />
                                </div>
                                <div className="md:col-span-3">
                                    <Button type="submit" disabled={processing}>
                                        Add match
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                ) : null}

                <div className="space-y-3">
                    {matches.map((match) => (
                        <div key={match.id} className="flex items-center justify-between rounded-xl border p-4">
                            <div>
                                <div className="font-medium">
                                    {match.home_team_name} vs {match.away_team_name}
                                </div>
                                <div className="text-sm text-muted-foreground">
                                    {new Date(match.starts_at).toLocaleString()} • {match.status_label}
                                    {match.venue ? ` • ${match.venue}` : ''}
                                </div>
                            </div>

                            {canManageMatches ? (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => {
                                        router.delete(`/${teamSlug}/admin/matches/${match.id}`);
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
