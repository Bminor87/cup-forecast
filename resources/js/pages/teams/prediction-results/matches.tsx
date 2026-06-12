import { Form, Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TournamentAdminNav from '@/components/teams/tournament-admin-nav';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import predictionResultsRoutes from '@/routes/teams/prediction-results';
import predictionScoresRoutes from '@/routes/teams/prediction-scores';

type ResultStatus = { value: string; label: string };

type MatchFieldResult = {
    id: number;
    label: string;
    field_type: string;
    result: {
        id: number;
        status: string;
        value: Record<string, unknown>;
        resolved_at: string | null;
    } | null;
};

type MatchResultGroup = {
    id: number;
    name: string;
    starts_at: string;
    fields: MatchFieldResult[];
};

type Props = {
    teamSlug: string;
    matches: MatchResultGroup[];
    resultStatuses: ResultStatus[];
    canManageResults: boolean;
};

export default function MatchPredictionResults({
    teamSlug,
    matches,
    resultStatuses,
    canManageResults,
}: Props) {
    return (
        <>
            <Head title="Match Results" />

            <div className="space-y-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        variant="small"
                        title="Match Results"
                        description="Create and update official match-scoped results"
                    />

                    <div className="flex items-center gap-2">
                        <Link
                            href={predictionResultsRoutes.tournament.url(teamSlug)}
                            className="text-sm underline-offset-4 hover:underline"
                        >
                            Tournament results
                        </Link>

                        {canManageResults ? (
                            <Form
                                action={predictionScoresRoutes.recalculate.url(teamSlug)}
                                method="post"
                            >
                                {({ processing }) => (
                                    <Button type="submit" size="sm" disabled={processing}>
                                        Recalculate scores
                                    </Button>
                                )}
                            </Form>
                        ) : null}
                    </div>
                </div>

                <TournamentAdminNav teamSlug={teamSlug} />

                <div className="space-y-5">
                    {matches.map((match) => (
                        <div key={match.id} className="rounded-lg border p-4">
                            <div className="mb-3">
                                <div className="font-medium">{match.name}</div>
                                <div className="text-sm text-muted-foreground">
                                    Starts: {new Date(match.starts_at).toLocaleString()}
                                </div>
                            </div>

                            <div className="space-y-3">
                                {match.fields.map((field) => (
                                    <Form
                                        key={field.id}
                                        action={predictionResultsRoutes.upsert.url([teamSlug, field.id])}
                                        method="put"
                                        className="grid gap-3 rounded-md border p-3 md:grid-cols-4"
                                    >
                                        {({ errors, processing }) => (
                                            <>
                                                <input
                                                    type="hidden"
                                                    name="tournament_match_id"
                                                    value={match.id}
                                                />

                                                <div className="md:col-span-1">
                                                    <div className="font-medium">{field.label}</div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {field.field_type}
                                                    </div>
                                                </div>

                                                <div className="grid gap-2 md:col-span-2">
                                                    <Label>Result JSON</Label>
                                                    <Input
                                                        name="result_value"
                                                        defaultValue={JSON.stringify(field.result?.value ?? {})}
                                                    />
                                                    <InputError message={errors.value} />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label>Status</Label>
                                                    <select
                                                        name="status"
                                                        className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                                        defaultValue={field.result?.status ?? resultStatuses[0]?.value}
                                                    >
                                                        {resultStatuses.map((status) => (
                                                            <option key={status.value} value={status.value}>
                                                                {status.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>

                                                <div className="md:col-span-4 flex items-center justify-between">
                                                    <div className="text-sm text-muted-foreground">
                                                        {field.result?.resolved_at
                                                            ? `Resolved at ${new Date(field.result.resolved_at).toLocaleString()}`
                                                            : 'No official result yet'}
                                                    </div>

                                                    {canManageResults ? (
                                                        <Button type="submit" size="sm" disabled={processing}>
                                                            Save result
                                                        </Button>
                                                    ) : null}
                                                </div>
                                            </>
                                        )}
                                    </Form>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}

MatchPredictionResults.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Match Results',
            href: props.currentTeam
                ? predictionResultsRoutes.matches.url(props.currentTeam.slug)
                : '/settings/teams/results/matches',
        },
    ],
});
