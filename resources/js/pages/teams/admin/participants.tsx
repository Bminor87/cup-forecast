import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import TournamentAdminNav from '@/components/teams/tournament-admin-nav';

type Participant = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    rank: number;
    points: number;
};

type Props = {
    teamSlug: string;
    participants: Participant[];
};

export default function AdminParticipantsPage({ teamSlug, participants }: Props) {
    return (
        <>
            <Head title="Admin Participants" />

            <div className="space-y-6 rounded-xl p-4">
                <Heading
                    variant="small"
                    title="Admin: Participants"
                    description="View tournament leaderboard and participant rankings."
                />

                <TournamentAdminNav teamSlug={teamSlug} />

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">Rank</th>
                                <th className="px-4 py-3 text-left font-medium">Name</th>
                                <th className="px-4 py-3 text-left font-medium">Email</th>
                                <th className="px-4 py-3 text-right font-medium">Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            {participants.length > 0 ? (
                                participants.map((participant) => (
                                    <tr key={participant.id} className="border-b hover:bg-muted/50">
                                        <td className="px-4 py-3">{participant.rank}</td>
                                        <td className="px-4 py-3 font-medium">{participant.name}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{participant.email}</td>
                                        <td className="px-4 py-3 text-right font-semibold">{participant.points}</td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={4} className="px-4 py-8 text-center text-muted-foreground">
                                        No participants yet
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
