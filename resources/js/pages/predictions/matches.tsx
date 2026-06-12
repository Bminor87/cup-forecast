import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import ParticipantNav from '@/components/predictions/participant-nav';
import PredictionFieldRenderer from '@/components/predictions/prediction-field-renderer';
import { Badge } from '@/components/ui/badge';
import type { MatchPredictionGroup, ParticipantPredictionField } from '@/types';

type Props = {
    currentTeamSlug: string;
    matchPredictions: MatchPredictionGroup[];
};

export default function MatchPredictionsPage({ currentTeamSlug, matchPredictions }: Props) {
    const savePrediction = async (field: ParticipantPredictionField, value: unknown): Promise<void> => {
        if (field.is_locked) {
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
                tournament_match_id: field.tournament_match_id,
            }),
        });

        if (!response.ok) {
            return;
        }

        window.location.reload();
    };

    return (
        <>
            <Head title="Match Predictions" />

            <div className="space-y-6 rounded-xl p-4">
                <Heading
                    variant="small"
                    title="Match Predictions"
                    description="Matches are the focus: submit picks inside each fixture card."
                />

                <ParticipantNav currentTeamSlug={currentTeamSlug} />

                <section className="space-y-5">
                    {matchPredictions.map((group) => (
                        <article key={group.match.id} className="rounded-xl border p-4">
                            <header className="mb-4 space-y-1">
                                <div className="text-lg font-semibold">
                                    {group.match.home_team_name} vs {group.match.away_team_name}
                                </div>
                                <div className="text-sm text-muted-foreground">
                                    Starts: {new Date(group.match.starts_at).toLocaleString()}
                                    {group.match.locks_at
                                        ? ` • Locks: ${new Date(group.match.locks_at).toLocaleString()}`
                                        : ''}
                                </div>
                            </header>

                            <div className="space-y-4">
                                {group.fields.map((field) => (
                                    <div key={field.id} className="rounded-lg border p-3">
                                        <div className="mb-2 flex flex-wrap items-center gap-2">
                                            <div className="font-medium">{field.label}</div>
                                            <Badge variant={field.is_locked ? 'destructive' : 'secondary'}>
                                                {field.is_locked ? 'Locked' : 'Open'}
                                            </Badge>
                                        </div>

                                        {field.description ? (
                                            <div className="mb-2 text-sm text-muted-foreground">{field.description}</div>
                                        ) : null}

                                        <PredictionFieldRenderer
                                            id={`${field.id}-${field.context_key}`}
                                            fieldType={field.field_type}
                                            value={field.value}
                                            disabled={field.is_locked}
                                            validationSchema={field.validation_schema}
                                            options={field.options}
                                            onChange={(nextValue) => {
                                                void savePrediction(field, nextValue);
                                            }}
                                        />
                                    </div>
                                ))}
                            </div>
                        </article>
                    ))}
                </section>
            </div>
        </>
    );
}

function csrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]');

    return meta?.getAttribute('content') ?? '';
}
