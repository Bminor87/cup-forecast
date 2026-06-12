import { Link, usePage } from '@inertiajs/react';
import { BookOpen, FolderGit2, LayoutGrid } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { TeamSwitcher } from '@/components/team-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const teamSlug = page.props.currentTeam?.slug;
    const dashboardUrl = teamSlug
        ? dashboard(teamSlug)
        : '/';

    const mainNavItems: NavItem[] = [
        {
            title: 'Overview',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        {
            title: 'Tournament Predictions',
            href: teamSlug ? `/${teamSlug}/predictions/tournament` : '/',
            icon: LayoutGrid,
        },
        {
            title: 'Match Predictions',
            href: teamSlug ? `/${teamSlug}/predictions/matches` : '/',
            icon: LayoutGrid,
        },
        {
            title: 'Leaderboard',
            href: teamSlug ? `/${teamSlug}/leaderboard` : '/',
            icon: LayoutGrid,
        },
        {
            title: 'Rules',
            href: teamSlug ? `/${teamSlug}/rules` : '/',
            icon: LayoutGrid,
        },
        {
            title: 'Admin: Questions',
            href: teamSlug ? `/${teamSlug}/admin/prediction-questions` : '/',
            icon: LayoutGrid,
        },
        {
            title: 'Admin: Results',
            href: teamSlug ? `/${teamSlug}/admin/results/tournament` : '/',
            icon: LayoutGrid,
        },
    ];

    const footerNavItems: NavItem[] = [
        {
            title: 'Repository',
            href: 'https://github.com/laravel/react-starter-kit',
            icon: FolderGit2,
        },
        {
            title: 'Documentation',
            href: 'https://laravel.com/docs/starter-kits#react',
            icon: BookOpen,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <TeamSwitcher />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
