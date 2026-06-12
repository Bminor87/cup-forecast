import { Head } from '@inertiajs/react';
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
                <section className="space-y-4 rounded-xl border p-5">
                    <Heading
                        variant="small"
                        title="Tournament Predictions"
                        description="One answer per tournament-scoped prediction field"
                    />

                    <div className="space-y-4">
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

                <section className="space-y-4 rounded-xl border p-5">
                    <Heading
                        variant="small"
                        title="Match Predictions"
                        description="One answer per field for each match"
                    />

                    <div className="space-y-6">
                        {matchPredictions.map((group) => (
                            <div key={group.match.id} className="space-y-3 rounded-lg border p-4">
                                <div className="space-y-1">
                                    <div className="font-medium">{group.match.name}</div>
                                    <div className="text-sm text-muted-foreground">
                                        Starts: {new Date(group.match.starts_at).toLocaleString()}
                                        {group.match.locks_at
                                            ? ` • Locks: ${new Date(group.match.locks_at).toLocaleString()}`
                                            : ''}
                                    </div>
                                </div>

                                <div className="space-y-4">
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
        <div className="space-y-3 rounded-lg border p-4" data-test="participant-prediction-field-row">
            <div className="flex flex-wrap items-center gap-2">
                <div className="font-medium">{field.label}</div>
                <Badge variant={state.isLocked ? 'destructive' : 'secondary'}>{lockBadge}</Badge>
                <Badge variant="outline">{state.status}</Badge>
                {state.saving ? <Badge variant="secondary">Saving...</Badge> : null}
            </div>

            {field.description ? (
                <div className="text-sm text-muted-foreground">{field.description}</div>
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

            <div className="text-xs text-muted-foreground">Last saved: {lastSaved}</div>

            {field.result_status ? (
                <div className="rounded-md border px-3 py-2 text-sm">
                    <div className="font-medium">Official result</div>
                    {field.result_is_visible ? (
                        <div>{String(field.result_value)}</div>
                    ) : (
                        <div className="text-muted-foreground">Result hidden by visibility rule.</div>
                    )}
                </div>
            ) : null}

            {state.error ? (
                <div className="text-sm text-destructive">{state.error}</div>
            ) : null}
        </div>
    );
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
