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
                <Heading
                    variant="small"
                    title="Admin: Teams"
                    description="Manage tournament teams used in predictions and fixtures."
                />

                <TournamentAdminNav teamSlug={teamSlug} />

                {canManageTournamentTeams ? (
                    <Form
                        action={`/${teamSlug}/admin/teams`}
                        method="post"
                        className="grid gap-4 rounded-xl border p-4 md:grid-cols-3"
                    >
                        {({ errors, processing }) => (
                            <>
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
                                        Add team
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                ) : null}

                <div className="space-y-3">
                    {tournamentTeams.map((team) => (
                        <div key={team.id} className="flex items-center justify-between rounded-xl border p-4">
                            <div>
                                <div className="font-medium">{team.name}</div>
                                <div className="text-sm text-muted-foreground">
                                    {team.type_label}
                                    {team.short_name ? ` • ${team.short_name}` : ''}
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
