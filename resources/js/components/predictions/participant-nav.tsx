import { Link } from '@inertiajs/react';
import { useCurrentUrl } from '@/hooks/use-current-url';

type Props = {
    currentTeamSlug: string;
};

export default function ParticipantNav({ currentTeamSlug }: Props) {
    const { isCurrentUrl } = useCurrentUrl();

    const items = [
        { label: 'Overview', href: `/${currentTeamSlug}/dashboard` },
        { label: 'Tournament Predictions', href: `/${currentTeamSlug}/predictions/tournament` },
        { label: 'Match Predictions', href: `/${currentTeamSlug}/predictions/matches` },
        { label: 'Leaderboard', href: `/${currentTeamSlug}/leaderboard` },
        { label: 'Rules', href: `/${currentTeamSlug}/rules` },
    ];

    return (
        <nav className="overflow-x-auto" aria-label="Participant navigation">
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
