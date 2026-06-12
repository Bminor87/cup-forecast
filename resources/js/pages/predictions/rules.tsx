import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import ParticipantNav from '@/components/predictions/participant-nav';

type Props = {
    currentTeamSlug: string;
    rules: {
        tournament_fields: string[];
        match_fields: string[];
        lock_rule: string;
        scoring_rule: string;
    };
};

export default function RulesPage({ currentTeamSlug, rules }: Props) {
    return (
        <>
            <Head title="Rules" />

            <div className="space-y-6 rounded-xl p-4">
                <Heading
                    variant="small"
                    title="Rules"
                    description="How predictions work in this tournament."
                />

                <ParticipantNav currentTeamSlug={currentTeamSlug} />

                <section className="space-y-4 rounded-xl border p-4">
                    <div className="font-medium">Tournament Prediction Questions</div>
                    <ul className="list-disc space-y-1 pl-5 text-sm text-muted-foreground">
                        {rules.tournament_fields.map((field) => (
                            <li key={field}>{field}</li>
                        ))}
                    </ul>
                </section>

                <section className="space-y-4 rounded-xl border p-4">
                    <div className="font-medium">Match Prediction Questions</div>
                    <ul className="list-disc space-y-1 pl-5 text-sm text-muted-foreground">
                        {rules.match_fields.map((field) => (
                            <li key={field}>{field}</li>
                        ))}
                    </ul>
                </section>

                <section className="space-y-2 rounded-xl border p-4 text-sm text-muted-foreground">
                    <div>{rules.lock_rule}</div>
                    <div>{rules.scoring_rule}</div>
                </section>
            </div>
        </>
    );
}
