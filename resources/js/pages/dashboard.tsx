import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import ParticipantNav from '@/components/predictions/participant-nav';
import { dashboard } from '@/routes';

type ParticipantSummary = {
    rank: number | null;
    points: number;
    submitted_predictions: number;
    remaining_predictions: number;
};

type UpcomingMatch = {
    id: number;
    home_team_name: string;
    away_team_name: string;
    starts_at: string;
    locks_at: string | null;
};

type UpcomingDeadline = {
    id: number;
    match_name: string;
    locks_at: string | null;
};

type Props = {
    currentTeamSlug: string | null;
    participantSummary: ParticipantSummary | null;
    upcomingMatches: UpcomingMatch[];
    upcomingDeadlines: UpcomingDeadline[];
};

export default function Dashboard({
    currentTeamSlug,
    participantSummary,
    upcomingMatches,
    upcomingDeadlines,
}: Props) {
    if (!currentTeamSlug) {
        return (
            <>
                <Head title="Dashboard" />
                <div className="space-y-4 rounded-xl border p-6">
                    <Heading
                        variant="small"
                        title="Overview"
                        description="Select a team to view tournament predictions."
                    />
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Overview" />

            <div className="space-y-6 rounded-xl p-4">
                <Heading
                    variant="small"
                    title="Overview"
                    description="Track your standing, progress, and upcoming deadlines."
                />

                <ParticipantNav currentTeamSlug={currentTeamSlug} />

                <section className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <SummaryCard
                        label="Current Rank"
                        value={participantSummary?.rank ? `#${participantSummary.rank}` : 'N/A'}
                    />
                    <SummaryCard
                        label="Current Points"
                        value={String(participantSummary?.points ?? 0)}
                    />
                    <SummaryCard
                        label="Submitted Predictions"
                        value={String(participantSummary?.submitted_predictions ?? 0)}
                    />
                    <SummaryCard
                        label="Remaining Predictions"
                        value={String(participantSummary?.remaining_predictions ?? 0)}
                    />
                </section>

                <section className="grid gap-4 lg:grid-cols-2">
                    <div className="rounded-xl border p-4">
                        <div className="mb-3 font-medium">Upcoming Matches</div>
                        <div className="space-y-2">
                            {upcomingMatches.length === 0 ? (
                                <div className="text-sm text-muted-foreground">No upcoming matches yet.</div>
                            ) : (
                                upcomingMatches.map((match) => (
                                    <div key={match.id} className="rounded-md border p-3">
                                        <div className="font-medium">
                                            {match.home_team_name} vs {match.away_team_name}
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            Starts: {new Date(match.starts_at).toLocaleString()}
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            Lock: {match.locks_at ? new Date(match.locks_at).toLocaleString() : 'Match start'}
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>

                    <div className="rounded-xl border p-4">
                        <div className="mb-3 font-medium">Upcoming Deadlines</div>
                        <div className="space-y-2">
                            {upcomingDeadlines.length === 0 ? (
                                <div className="text-sm text-muted-foreground">No deadlines scheduled.</div>
                            ) : (
                                upcomingDeadlines.map((deadline) => (
                                    <div key={deadline.id} className="rounded-md border p-3">
                                        <div className="font-medium">{deadline.match_name}</div>
                                        <div className="text-sm text-muted-foreground">
                                            Locks at {deadline.locks_at ? new Date(deadline.locks_at).toLocaleString() : 'Unknown'}
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}

type SummaryCardProps = {
    label: string;
    value: string;
};

function SummaryCard({ label, value }: SummaryCardProps) {
    return (
        <article className="rounded-xl border p-4">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">{label}</div>
            <div className="mt-1 text-2xl font-semibold">{value}</div>
        </article>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Overview',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
