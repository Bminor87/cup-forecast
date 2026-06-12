import { Form, Head, router } from '@inertiajs/react';
import { Shield, Trophy, X } from 'lucide-react';
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
    short_name: string | null;
    type_label: string;
};

type TournamentOption = {
    value: string;
    label: string;
};

type Props = {
    teamSlug: string;
    tournamentTeams: TournamentTeam[];
    teamTypes: TournamentOption[];
    canManageTournamentTeams: boolean;
};

export default function AdminTeamsPage({
    teamSlug,
    tournamentTeams,
    teamTypes,
    canManageTournamentTeams,
}: Props) {
    return (
        <>
            <Head title="Admin Teams" />

            <div className="space-y-6 rounded-xl p-4">
                <section className="rounded-2xl border border-sky-900/50 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 p-6">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <Heading
                            variant="small"
                            title="Tournament Teams"
                            description="Build the official competition roster used in fixtures, predictions, and results."
                        />

                        <div className="flex items-center gap-2">
                            <Badge variant="outline" className="border-sky-700/70 bg-sky-500/10 text-sky-200">
                                <Trophy className="mr-1 h-3 w-3" />
                                {tournamentTeams.length} registered
                            </Badge>
                        </div>
                    </div>
                </section>

                <TournamentAdminNav teamSlug={teamSlug} />

                {canManageTournamentTeams ? (
                    <Form
                        action={`/${teamSlug}/admin/teams`}
                        method="post"
                        className="grid gap-4 rounded-xl border border-slate-800 bg-slate-950/60 p-5 md:grid-cols-3"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="md:col-span-3">
                                    <p className="text-sm font-medium text-slate-100">Add a competing team</p>
                                    <p className="text-xs text-slate-400">Use official names so participants recognize fixtures instantly.</p>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="name">Team name</Label>
                                    <Input id="name" name="name" required />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="short_name">Short name</Label>
                                    <Input id="short_name" name="short_name" />
                                    <InputError message={errors.short_name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="type">Type</Label>
                                    <select
                                        id="type"
                                        name="type"
                                        className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                        defaultValue={teamTypes[0]?.value}
                                    >
                                        {teamTypes.map((type) => (
                                            <option key={type.value} value={type.value}>
                                                {type.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.type} />
                                </div>
                                <div className="md:col-span-3">
                                    <Button type="submit" disabled={processing}>
                                        Register team
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                ) : null}

                <div className="grid gap-3 md:grid-cols-2">
                    {tournamentTeams.map((team) => (
                        <div key={team.id} className="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-full border border-slate-700 bg-slate-900 text-slate-100">
                                    <Shield className="h-4 w-4" />
                                </div>

                                <div>
                                    <div className="font-medium text-slate-100">{team.name}</div>
                                    <div className="text-sm text-slate-400">
                                        {team.type_label}
                                        {team.short_name ? ` • ${team.short_name}` : ''}
                                    </div>
                                </div>
                            </div>

                            {canManageTournamentTeams ? (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => {
                                        router.delete(`/${teamSlug}/admin/teams/${team.id}`);
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
