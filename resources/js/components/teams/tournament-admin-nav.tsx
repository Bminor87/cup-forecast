import { Link } from '@inertiajs/react';
import { useCurrentUrl } from '@/hooks/use-current-url';

type Props = {
    teamSlug: string;
};

export default function TournamentAdminNav({ teamSlug }: Props) {
    const { isCurrentUrl } = useCurrentUrl();

    const items = [
        { label: 'Teams', href: `/${teamSlug}/admin/teams` },
        { label: 'Players', href: `/${teamSlug}/admin/players` },
        { label: 'Matches', href: `/${teamSlug}/admin/matches` },
        { label: 'Prediction Questions', href: `/${teamSlug}/admin/prediction-questions` },
        { label: 'Results', href: `/${teamSlug}/admin/results/tournament` },
        { label: 'Participants', href: `/${teamSlug}/admin/participants` },
    ];

    return (
        <nav className="overflow-x-auto" aria-label="Tournament admin navigation">
            <div className="flex min-w-max gap-2 rounded-xl border bg-card p-2">
                {items.map((item) => {
                    const active = isCurrentUrl(item.href);

                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={[
                                'rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                            ].join(' ')}
                        >
                            {item.label}
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
