import { Form } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TournamentAdminNav from '@/components/teams/tournament-admin-nav';
import { Button } from '@/components/ui/button';

type Template = {
    key: string;
    label: string;
    description: string;
    scope: string;
};

type PredictionField = {
    id: number;
    label: string;
    scope: string;
    field_type: string;
    option_source?: string | null;
    is_active: boolean;
    result_count: number;
};

type Visibility = {
    value: string;
    label: string;
};

type Props = {
    teamSlug: string;
    templates: Template[];
    predictionFields: PredictionField[];
    predictionVisibilities: Visibility[];
    canManagePredictionFields: boolean;
};

export default function AdminPredictionQuestionsPage({
    teamSlug,
    templates,
    predictionFields,
    canManagePredictionFields,
}: Props) {
    return (
        <>
            <Head title="Admin Prediction Questions" />

            <div className="space-y-6 rounded-xl p-4">
                <Heading
                    variant="small"
                    title="Admin: Prediction Questions"
                    description="Create questions with templates instead of JSON configuration."
                />

                <TournamentAdminNav teamSlug={teamSlug} />

                {canManagePredictionFields ? (
                    <div className="grid gap-3 md:grid-cols-2">
                        {templates.map((template) => (
                            <Form
                                key={template.key}
                                action={`/${teamSlug}/admin/prediction-fields/templates`}
                                method="post"
                                className="rounded-xl border p-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <input type="hidden" name="template_key" value={template.key} />
                                        <div className="font-medium">{template.label}</div>
                                        <div className="mt-1 text-sm text-muted-foreground">
                                            {template.description}
                                        </div>
                                        <div className="mt-2 text-xs uppercase tracking-wide text-muted-foreground">
                                            {template.scope}
                                        </div>
                                        <InputError message={errors.template_key} />
                                        <div className="mt-3">
                                            <Button type="submit" size="sm" disabled={processing}>
                                                Add Template
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        ))}
                    </div>
                ) : null}

                <div className="space-y-3">
                    {predictionFields.map((field) => (
                        <article key={field.id} className="rounded-xl border p-4">
                            <div className="font-medium">{field.label}</div>
                            <div className="text-sm text-muted-foreground">
                                {field.scope} • {field.field_type}
                                {field.option_source ? ` • ${field.option_source}` : ''}
                            </div>
                            <div className="mt-1 text-xs text-muted-foreground">
                                {field.result_count} official result{field.result_count === 1 ? '' : 's'} saved
                                {' • '}
                                {field.is_active ? 'Active' : 'Inactive'}
                            </div>
                        </article>
                    ))}
                </div>
            </div>
        </>
    );
}
