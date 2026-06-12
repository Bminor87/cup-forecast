import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import ParticipantNav from '@/components/predictions/participant-nav';

type LeaderboardRow = {
    rank: number;
    participant: string;
    points: number;
};

type Props = {
    currentTeamSlug: string;
    leaderboard: LeaderboardRow[];
};

export default function LeaderboardPage({ currentTeamSlug, leaderboard }: Props) {
    return (
        <>
            <Head title="Leaderboard" />

            <div className="space-y-6 rounded-xl p-4">
                <Heading
                    variant="small"
                    title="Leaderboard"
                    description="Live standings based on persisted scores."
                />

                <ParticipantNav currentTeamSlug={currentTeamSlug} />

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3">Rank</th>
                                <th className="px-4 py-3">Participant</th>
                                <th className="px-4 py-3 text-right">Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            {leaderboard.map((row) => (
                                <tr key={`${row.rank}-${row.participant}`} className="border-t">
                                    <td className="px-4 py-3 font-medium">#{row.rank}</td>
                                    <td className="px-4 py-3">{row.participant}</td>
                                    <td className="px-4 py-3 text-right font-semibold">{row.points}</td>
                                </tr>
                            ))}
                            {leaderboard.length === 0 ? (
                                <tr>
                                    <td colSpan={3} className="px-4 py-6 text-center text-muted-foreground">
                                        No scores yet.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
