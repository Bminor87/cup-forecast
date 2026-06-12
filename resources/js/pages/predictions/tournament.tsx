import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import ParticipantNav from '@/components/predictions/participant-nav';
import PredictionFieldRenderer from '@/components/predictions/prediction-field-renderer';
import { Badge } from '@/components/ui/badge';
import type { ParticipantPredictionField, PredictionStatus } from '@/types';

type Props = {
    currentTeamSlug: string;
    tournamentPredictions: ParticipantPredictionField[];
};

type PredictionState = {
    value: unknown;
    status: PredictionStatus;
    isLocked: boolean;
    saving: boolean;
    error: string | null;
    lastSavedAt: string | null;
};

export default function TournamentPredictionsPage({ currentTeamSlug, tournamentPredictions }: Props) {
    const stateByKey = new Map<string, PredictionState>();

    for (const field of tournamentPredictions) {
        stateByKey.set(field.context_key + ':' + field.id, {
            value: field.value,
            status: field.status,
            isLocked: field.is_locked,
            saving: false,
            error: null,
            lastSavedAt: field.last_saved_at,
        });
    }

    const savePrediction = async (field: ParticipantPredictionField, value: unknown): Promise<void> => {
        const key = field.context_key + ':' + field.id;
        const snapshot = stateByKey.get(key);

        if (!snapshot || snapshot.isLocked) {
            return;
        }

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
                tournament_match_id: null,
            }),
        });

        if (!response.ok) {
            return;
        }

        window.location.reload();
    };

    return (
        <>
            <Head title="Tournament Predictions" />

            <div className="space-y-6 rounded-xl p-4">
                <Heading
                    variant="small"
                    title="Tournament Predictions"
                    description="Set your long-term picks for the whole tournament."
                />

                <ParticipantNav currentTeamSlug={currentTeamSlug} />

                <section className="space-y-4">
                    {tournamentPredictions.map((field) => {
                        const localState = stateByKey.get(field.context_key + ':' + field.id);

                        return (
                            <article key={field.id} className="rounded-xl border p-4">
                                <div className="mb-3 flex flex-wrap items-center gap-2">
                                    <div className="font-medium">{field.label}</div>
                                    <Badge variant={field.is_locked ? 'destructive' : 'secondary'}>
                                        {field.is_locked ? 'Locked' : 'Open'}
                                    </Badge>
                                    <Badge variant="outline">{field.status}</Badge>
                                </div>

                                {field.description ? (
                                    <div className="mb-3 text-sm text-muted-foreground">{field.description}</div>
                                ) : null}

                                <PredictionFieldRenderer
                                    id={`${field.id}-${field.context_key}`}
                                    fieldType={field.field_type}
                                    value={localState?.value ?? null}
                                    disabled={field.is_locked}
                                    validationSchema={field.validation_schema}
                                    options={field.options}
                                    onChange={(nextValue) => {
                                        void savePrediction(field, nextValue);
                                    }}
                                />

                                <div className="mt-3 text-xs text-muted-foreground">
                                    {field.last_saved_at
                                        ? `Saved ${new Date(field.last_saved_at).toLocaleString()}`
                                        : 'Not saved yet'}
                                </div>
                            </article>
                        );
                    })}
                </section>
            </div>
        </>
    );
}

function csrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]');

    return meta?.getAttribute('content') ?? '';
}
