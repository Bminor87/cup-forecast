import { Link } from '@inertiajs/react';
import { CalendarDays, ClipboardCheck, Trophy, Users, UsersRound } from 'lucide-react';
import { useCurrentUrl } from '@/hooks/use-current-url';

type Props = {
    teamSlug: string;
};

export default function TournamentAdminNav({ teamSlug }: Props) {
    const { isCurrentUrl } = useCurrentUrl();

    const items = [
        { label: 'Teams', href: `/${teamSlug}/admin/teams`, icon: Trophy },
        { label: 'Players', href: `/${teamSlug}/admin/players`, icon: Users },
        { label: 'Fixtures', href: `/${teamSlug}/admin/matches`, icon: CalendarDays },
        { label: 'Prediction Playbook', href: `/${teamSlug}/admin/prediction-questions`, icon: ClipboardCheck },
        { label: 'Results Desk', href: `/${teamSlug}/admin/results/tournament`, icon: ClipboardCheck },
        { label: 'Leaderboard', href: `/${teamSlug}/admin/participants`, icon: UsersRound },
    ];

    return (
        <nav className="overflow-x-auto" aria-label="Tournament admin navigation">
            <div className="flex min-w-max gap-2 rounded-xl border border-sky-900/50 bg-gradient-to-r from-slate-950 to-slate-900 p-2">
                {items.map((item) => {
                    const active = isCurrentUrl(item.href);
                    const Icon = item.icon;

                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={[
                                'inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                active
                                    ? 'bg-sky-500/20 text-sky-200'
                                    : 'text-slate-300 hover:bg-slate-800 hover:text-white',
                            ].join(' ')}
                        >
                            <Icon className="h-4 w-4" />
                            {item.label}
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
