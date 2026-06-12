import { Form, Head, router } from '@inertiajs/react';
import { CalendarDays, Clock3, MapPin, X } from 'lucide-react';
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
                <section className="rounded-2xl border border-sky-900/50 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 p-6">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <Heading
                            variant="small"
                            title="Fixture Board"
                            description="Schedule kickoff times and venues before opening predictions to participants."
                        />

                        <Badge variant="outline" className="border-sky-700/70 bg-sky-500/10 text-sky-200">
                            <CalendarDays className="mr-1 h-3 w-3" />
                            {matches.length} fixtures
                        </Badge>
                    </div>
                </section>

                <TournamentAdminNav teamSlug={teamSlug} />

                {canManageMatches && tournamentTeams.length >= 2 ? (
                    <Form
                        action={`/${teamSlug}/admin/matches`}
                        method="post"
                        className="grid gap-4 rounded-xl border border-slate-800 bg-slate-950/60 p-5 md:grid-cols-3"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="md:col-span-3 text-sm text-slate-200">Create a new fixture</div>

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
                                        Publish fixture
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                ) : null}

                <div className="space-y-3">
                    {matches.map((match) => (
                        <div key={match.id} className="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                            <div className="space-y-2">
                                <div className="font-medium text-slate-100">
                                    {match.home_team_name} vs {match.away_team_name}
                                </div>
                                <div className="flex flex-wrap items-center gap-2 text-sm text-slate-400">
                                    <span className="inline-flex items-center gap-1">
                                        <Clock3 className="h-4 w-4" />
                                        {new Date(match.starts_at).toLocaleString()}
                                    </span>
                                    <span>•</span>
                                    <span>{match.status_label}</span>
                                    {match.venue ? (
                                        <>
                                            <span>•</span>
                                            <span className="inline-flex items-center gap-1">
                                                <MapPin className="h-4 w-4" />
                                                {match.venue}
                                            </span>
                                        </>
                                    ) : null}
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
