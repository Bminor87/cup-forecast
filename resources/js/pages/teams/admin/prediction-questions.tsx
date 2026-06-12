import { Form } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { ClipboardCheck, Goal, Sparkles, Trophy } from 'lucide-react';
import type { ReactNode } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TournamentAdminNav from '@/components/teams/tournament-admin-nav';
import { Badge } from '@/components/ui/badge';
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
    const tournamentTemplates = templates.filter((template) => template.scope === 'tournament');
    const matchTemplates = templates.filter((template) => template.scope === 'match');

    return (
        <>
            <Head title="Admin Prediction Questions" />

            <div className="space-y-6 rounded-xl p-4">
                <section className="rounded-2xl border border-sky-900/50 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 p-6">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <Heading
                            variant="small"
                            title="Prediction Playbook"
                            description="Activate prediction markets for tournament awards and every fixture."
                        />

                        <Badge variant="outline" className="border-sky-700/70 bg-sky-500/10 text-sky-200">
                            <ClipboardCheck className="mr-1 h-3 w-3" />
                            {predictionFields.length} active markets
                        </Badge>
                    </div>
                </section>

                <TournamentAdminNav teamSlug={teamSlug} />

                {canManagePredictionFields ? (
                    <div className="space-y-4">
                        <TemplateBlock
                            title="Tournament Awards"
                            description="Winner, medalists, and individual honors."
                            icon={<Trophy className="h-4 w-4" />}
                            templates={tournamentTemplates}
                            teamSlug={teamSlug}
                        />

                        <TemplateBlock
                            title="Matchday Markets"
                            description="Winner, exact score, MVP, and first goalscorer by fixture."
                            icon={<Goal className="h-4 w-4" />}
                            templates={matchTemplates}
                            teamSlug={teamSlug}
                        />
                    </div>
                ) : null}

                <section className="space-y-3">
                    <div className="flex items-center gap-2 text-sm font-medium text-slate-200">
                        <Sparkles className="h-4 w-4" />
                        Live prediction markets
                    </div>

                    <div className="grid gap-3 md:grid-cols-2">
                        {predictionFields.map((field) => (
                            <article key={field.id} className="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                                <div className="flex flex-wrap items-center gap-2">
                                    <div className="font-medium text-slate-100">{field.label}</div>
                                    <Badge variant="secondary" className="bg-slate-800 text-slate-200">
                                        {field.scope}
                                    </Badge>
                                    <Badge variant="outline" className="border-slate-700 text-slate-300">
                                        {field.field_type}
                                    </Badge>
                                </div>

                                <div className="mt-2 text-sm text-slate-400">
                                    {field.option_source ? `Option source: ${field.option_source}` : 'Free input'}
                                </div>

                                <div className="mt-2 text-xs text-slate-500">
                                    {field.result_count} official result{field.result_count === 1 ? '' : 's'} saved
                                    {' • '}
                                    {field.is_active ? 'Active' : 'Inactive'}
                                </div>
                            </article>
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}

type TemplateBlockProps = {
    title: string;
    description: string;
    icon: ReactNode;
    templates: Template[];
    teamSlug: string;
};

function TemplateBlock({ title, description, icon, templates, teamSlug }: TemplateBlockProps) {
    if (templates.length === 0) {
        return null;
    }

    return (
        <section className="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
            <div className="mb-4 flex items-center gap-2 text-sm font-medium text-slate-200">
                {icon}
                {title}
            </div>

            <p className="mb-4 text-xs text-slate-400">{description}</p>

            <div className="grid gap-3 md:grid-cols-2">
                {templates.map((template) => (
                            <Form
                                key={template.key}
                                action={`/${teamSlug}/admin/prediction-fields/templates`}
                                method="post"
                                className="rounded-xl border border-slate-800 bg-slate-950/70 p-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <input type="hidden" name="template_key" value={template.key} />
                                        <div className="font-medium text-slate-100">{template.label}</div>
                                        <div className="mt-1 text-sm text-slate-400">
                                            {template.description}
                                        </div>
                                        <div className="mt-2 text-xs uppercase tracking-wide text-slate-500">
                                            {template.scope}
                                        </div>
                                        <InputError message={errors.template_key} />
                                        <div className="mt-3">
                                            <Button type="submit" size="sm" disabled={processing}>
                                                Activate market
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                ))}
                    </div>
        </section>
    );
}
