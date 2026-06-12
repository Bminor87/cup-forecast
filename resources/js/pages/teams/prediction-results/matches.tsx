import { Form, Head, Link } from '@inertiajs/react';
import { CalendarClock, ClipboardCheck, RotateCcw } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TournamentAdminNav from '@/components/teams/tournament-admin-nav';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import predictionResultsRoutes from '@/routes/admin/prediction-results';
import predictionScoresRoutes from '@/routes/admin/prediction-scores';

type ResultStatus = { value: string; label: string };

type MatchFieldResult = {
    id: number;
    label: string;
    field_type: string;
    options: { value: string | number | boolean; label: string }[];
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
    home_team_name: string;
    away_team_name: string;
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
                <section className="rounded-2xl border border-sky-900/50 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 p-6">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <Heading
                            variant="small"
                            title="Match Results Desk"
                            description="Set official final outcomes for each fixture and settle participant picks."
                        />

                        <div className="flex items-center gap-2">
                            <Link
                                href={predictionResultsRoutes.tournament.url(teamSlug)}
                                className="text-sm text-sky-300 underline-offset-4 hover:underline"
                            >
                                Switch to tournament results
                            </Link>

                            {canManageResults ? (
                                <Form
                                    action={predictionScoresRoutes.recalculate.url(teamSlug)}
                                    method="post"
                                >
                                    {({ processing }) => (
                                        <Button type="submit" size="sm" disabled={processing}>
                                            <RotateCcw className="mr-1 h-4 w-4" />
                                            Recalculate scores
                                        </Button>
                                    )}
                                </Form>
                            ) : null}
                        </div>
                    </div>
                </section>

                <TournamentAdminNav teamSlug={teamSlug} />

                <div className="space-y-5">
                    {matches.map((match) => (
                        <div key={match.id} className="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                            <div className="mb-4 flex flex-wrap items-center gap-2">
                                <div className="font-medium text-slate-100">{match.name}</div>
                                <Badge variant="outline" className="border-slate-700 text-slate-300">
                                    <CalendarClock className="mr-1 h-3 w-3" />
                                    {new Date(match.starts_at).toLocaleString()}
                                </Badge>
                            </div>

                            <div className="grid gap-3 md:grid-cols-2">
                                {match.fields.map((field) => (
                                    <Form
                                        key={field.id}
                                        action={predictionResultsRoutes.upsert.url([teamSlug, field.id])}
                                        method="put"
                                        className="grid gap-3 rounded-md border border-slate-800 bg-slate-950/70 p-3"
                                    >
                                        {({ errors, processing }) => {
                                            const value = unwrapResultValue(field.result?.value);

                                            return (
                                                <>
                                                    <input
                                                        type="hidden"
                                                        name="tournament_match_id"
                                                        value={match.id}
                                                    />

                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <div className="font-medium text-slate-100">{field.label}</div>
                                                        <Badge variant="secondary" className="bg-slate-800 text-slate-200">
                                                            {field.field_type}
                                                        </Badge>
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label>Official result</Label>
                                                        <ResultValueInput
                                                            fieldType={field.field_type}
                                                            name="value"
                                                            value={value}
                                                            options={field.options}
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

                                                    <div className="flex items-center justify-between gap-3">
                                                        <div className="text-xs text-slate-500">
                                                            {field.result?.resolved_at
                                                                ? `Resolved ${new Date(field.result.resolved_at).toLocaleString()}`
                                                                : 'No official result yet'}
                                                        </div>

                                                        {canManageResults ? (
                                                            <Button type="submit" size="sm" disabled={processing}>
                                                                <ClipboardCheck className="mr-1 h-4 w-4" />
                                                                Save result
                                                            </Button>
                                                        ) : null}
                                                    </div>
                                                </>
                                            );
                                        }}
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

type ResultValueInputProps = {
    fieldType: string;
    name: string;
    value: unknown;
    options: { value: string | number | boolean; label: string }[];
};

function ResultValueInput({ fieldType, name, value, options }: ResultValueInputProps) {
    if (fieldType === 'team_picker' || fieldType === 'player_picker') {
        const normalized = value === null || value === undefined ? '' : String(value);

        return (
            <select
                name={name}
                className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                defaultValue={normalized}
            >
                <option value="">Select result</option>
                {options.map((option) => (
                    <option key={String(option.value)} value={String(option.value)}>
                        {option.label}
                    </option>
                ))}
            </select>
        );
    }

    if (fieldType === 'number') {
        return <Input name={name} type="number" defaultValue={value === null || value === undefined ? '' : String(value)} />;
    }

    if (fieldType === 'boolean') {
        const normalized = typeof value === 'boolean' ? String(value) : '';

        return (
            <select
                name={name}
                className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                defaultValue={normalized}
            >
                <option value="">Select result</option>
                <option value="true">Yes</option>
                <option value="false">No</option>
            </select>
        );
    }

    if (fieldType === 'date' || fieldType === 'time') {
        return <Input name={name} type={fieldType} defaultValue={typeof value === 'string' ? value : ''} />;
    }

    return <Input name={name} defaultValue={value === null || value === undefined ? '' : String(value)} />;
}

function unwrapResultValue(value: Record<string, unknown> | undefined): unknown {
    if (!value) {
        return null;
    }

    if (Object.prototype.hasOwnProperty.call(value, 'value')) {
        return value.value;
    }

    return value;
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
