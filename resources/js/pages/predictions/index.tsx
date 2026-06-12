import { Head } from '@inertiajs/react';
import { CalendarClock, CheckCircle2, Clock3, Lock, Save, ShieldCheck, Trophy } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import Heading from '@/components/heading';
import PredictionFieldRenderer from '@/components/predictions/prediction-field-renderer';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import type {
    MatchPredictionGroup,
    ParticipantPredictionField,
    PredictionStatus,
} from '@/types';

type PredictionState = {
    value: unknown;
    status: PredictionStatus;
    isLocked: boolean;
    lastSavedAt: string | null;
    saving: boolean;
    error: string | null;
};

type Props = {
    currentTeamSlug: string;
    tournamentPredictions: ParticipantPredictionField[];
    matchPredictions: MatchPredictionGroup[];
};

export default function PredictionsIndex({
    currentTeamSlug,
    tournamentPredictions,
    matchPredictions,
}: Props) {
    const fieldInstances = useMemo(
        () => [
            ...tournamentPredictions,
            ...matchPredictions.flatMap((group) => group.fields),
        ],
        [tournamentPredictions, matchPredictions],
    );

    const [predictionState, setPredictionState] = useState<Record<string, PredictionState>>(() =>
        Object.fromEntries(
            fieldInstances.map((field) => [
                keyFor(field),
                {
                    value: field.value,
                    status: field.status,
                    isLocked: field.is_locked,
                    lastSavedAt: field.last_saved_at,
                    saving: false,
                    error: null,
                },
            ]),
        ),
    );

    const saveTimers = useRef<Record<string, ReturnType<typeof setTimeout>>>({});

    useEffect(() => {
        const timers = saveTimers.current;

        return () => {
            Object.values(timers).forEach((timer) => clearTimeout(timer));
        };
    }, []);

    const updateValue = (field: ParticipantPredictionField, nextValue: unknown) => {
        const instanceKey = keyFor(field);

        setPredictionState((previous) => ({
            ...previous,
            [instanceKey]: {
                ...previous[instanceKey],
                value: nextValue,
                error: null,
            },
        }));

        if (predictionState[instanceKey]?.isLocked) {
            return;
        }

        scheduleSave(field, nextValue);
    };

    const scheduleSave = (field: ParticipantPredictionField, value: unknown) => {
        const instanceKey = keyFor(field);

        if (saveTimers.current[instanceKey]) {
            clearTimeout(saveTimers.current[instanceKey]);
        }

        saveTimers.current[instanceKey] = setTimeout(() => {
            void savePrediction(field, value);
        }, 450);
    };

    const savePrediction = async (field: ParticipantPredictionField, value: unknown) => {
        const instanceKey = keyFor(field);

        setPredictionState((previous) => ({
            ...previous,
            [instanceKey]: {
                ...previous[instanceKey],
                saving: true,
                error: null,
            },
        }));

        try {
            const response = await fetch(`/${currentTeamSlug}/predictions/${field.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    value,
                    status: 'submitted',
                    tournament_match_id: field.tournament_match_id,
                }),
            });

            if (!response.ok) {
                const payload = (await response.json()) as {
                    errors?: Record<string, string[]>;
                    message?: string;
                };

                const firstError = Object.values(payload.errors ?? {})[0]?.[0];

                throw new Error(firstError ?? payload.message ?? 'Could not save prediction.');
            }

            const payload = (await response.json()) as {
                status: PredictionStatus;
                locked: boolean;
                last_saved_at: string | null;
                value: unknown;
            };

            setPredictionState((previous) => ({
                ...previous,
                [instanceKey]: {
                    ...previous[instanceKey],
                    value: payload.value,
                    status: payload.status,
                    isLocked: payload.locked,
                    lastSavedAt: payload.last_saved_at,
                    saving: false,
                    error: null,
                },
            }));
        } catch (error) {
            setPredictionState((previous) => ({
                ...previous,
                [instanceKey]: {
                    ...previous[instanceKey],
                    saving: false,
                    error: error instanceof Error ? error.message : 'Could not save prediction.',
                },
            }));
        }
    };

    return (
        <>
            <Head title="Predictions" />

            <div className="space-y-8 rounded-xl p-4">
                <section className="rounded-2xl border border-sky-900/50 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 p-6">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <Heading
                            variant="small"
                            title="Match Center"
                            description="Submit your predictions by fixture. Picks save automatically as you update them."
                        />

                        <div className="flex items-center gap-2 text-xs text-slate-300">
                            <Badge variant="outline" className="border-sky-700/70 bg-sky-500/10 text-sky-200">
                                <Save className="mr-1 h-3 w-3" />
                                Auto-save enabled
                            </Badge>
                        </div>
                    </div>
                </section>

                {tournamentPredictions.length > 0 ? (
                    <section className="space-y-4 rounded-xl border border-slate-800 bg-slate-950/60 p-5">
                        <div className="flex items-center gap-2 text-slate-200">
                            <Trophy className="h-4 w-4" />
                            Tournament predictions
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            {tournamentPredictions.map((field) => (
                                <PredictionFieldCard
                                    key={keyFor(field)}
                                    field={field}
                                    state={predictionState[keyFor(field)]}
                                    onValueChange={updateValue}
                                />
                            ))}
                        </div>
                    </section>
                ) : null}

                <section className="space-y-4 rounded-xl border border-slate-800 bg-slate-950/60 p-5">
                    <Heading
                        variant="small"
                        title="Matchday Picks"
                        description="Fill in outcomes, scorers, and key events for each fixture."
                    />

                    <div className="space-y-6">
                        {matchPredictions.map((group) => (
                            <div key={group.match.id} className="space-y-4 rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                                <div className="space-y-1">
                                    <div className="font-medium text-slate-100">{group.match.name}</div>
                                    <div className="flex flex-wrap items-center gap-2 text-sm text-slate-400">
                                        <span className="inline-flex items-center gap-1">
                                            <CalendarClock className="h-4 w-4" />
                                            {new Date(group.match.starts_at).toLocaleString()}
                                        </span>
                                        {group.match.locks_at
                                            ? (
                                                <span className="inline-flex items-center gap-1">
                                                    <Lock className="h-4 w-4" />
                                                    Locks {new Date(group.match.locks_at).toLocaleString()}
                                                </span>
                                            )
                                            : null}
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    {group.fields.map((field) => (
                                        <PredictionFieldCard
                                            key={keyFor(field)}
                                            field={field}
                                            state={predictionState[keyFor(field)]}
                                            onValueChange={updateValue}
                                        />
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}

type PredictionFieldCardProps = {
    field: ParticipantPredictionField;
    state: PredictionState;
    onValueChange: (field: ParticipantPredictionField, value: unknown) => void;
};

function PredictionFieldCard({
    field,
    state,
    onValueChange,
}: PredictionFieldCardProps) {
    const lockBadge = state.isLocked ? 'Locked' : 'Open';
    const lastSaved = state.lastSavedAt
        ? new Date(state.lastSavedAt).toLocaleString()
        : 'Not saved yet';

    return (
        <div className="space-y-3 rounded-lg border border-slate-800 bg-slate-950/80 p-4" data-test="participant-prediction-field-row">
            <div className="flex flex-wrap items-center gap-2">
                <div className="font-medium text-slate-100">{field.label}</div>
                <Badge variant={state.isLocked ? 'destructive' : 'secondary'} className={state.isLocked ? '' : 'bg-emerald-500/15 text-emerald-200'}>
                    {state.isLocked ? <Lock className="mr-1 h-3 w-3" /> : <ShieldCheck className="mr-1 h-3 w-3" />}
                    {lockBadge}
                </Badge>
                <Badge variant="outline" className="border-slate-700 text-slate-300">{state.status}</Badge>
                {state.saving ? (
                    <Badge variant="secondary" className="bg-slate-800 text-slate-200">
                        <Clock3 className="mr-1 h-3 w-3" />
                        Saving
                    </Badge>
                ) : null}
            </div>

            {field.description ? (
                <div className="text-sm text-slate-400">{field.description}</div>
            ) : null}

            <PredictionFieldRenderer
                id={`${field.id}-${field.context_key}`}
                fieldType={field.field_type}
                value={state.value}
                disabled={state.isLocked}
                validationSchema={field.validation_schema}
                options={field.options}
                onChange={(value) => onValueChange(field, value)}
            />

            <div className="text-xs text-slate-500">Last saved: {lastSaved}</div>

            {field.result_status ? (
                <div className="rounded-md border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm">
                    <div className="flex items-center gap-1 font-medium text-slate-100">
                        <CheckCircle2 className="h-4 w-4" />
                        Official result
                    </div>
                    {field.result_is_visible ? (
                        <div className="mt-1 text-slate-200">{formatResultPreview(field.result_value)}</div>
                    ) : (
                        <div className="mt-1 text-slate-400">Result hidden until this market unlocks.</div>
                    )}
                </div>
            ) : null}

            {state.error ? (
                <div className="text-sm text-destructive">{state.error}</div>
            ) : null}
        </div>
    );
}

function formatResultPreview(value: unknown): string {
    if (value === null || value === undefined) {
        return 'No result value';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value);
    }

    return String(value);
}

function keyFor(field: ParticipantPredictionField): string {
    return `${field.id}:${field.context_key}`;
}

function csrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }

    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

PredictionsIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Predictions',
            href: props.currentTeam ? `/${props.currentTeam.slug}/predictions` : '/predictions',
        },
    ],
});
