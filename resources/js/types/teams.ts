export type TeamRole = 'owner' | 'admin' | 'member';

export type Team = {
    id: number;
    name: string;
    slug: string;
    isPersonal: boolean;
    role?: TeamRole;
    roleLabel?: string;
    isCurrent?: boolean;
};

export type TeamMember = {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    role: TeamRole;
    role_label: string;
};

export type TeamInvitation = {
    code: string;
    email: string;
    role: TeamRole;
    role_label: string;
    created_at: string;
};

export type TeamInvitationContext = {
    code: string;
    teamName: string;
};

export type DashboardInvitation = {
    code: string;
    inviterName: string;
    team: {
        name: string;
        slug: string;
    };
};

export type TeamPermissions = {
    canUpdateTeam: boolean;
    canDeleteTeam: boolean;
    canAddMember: boolean;
    canUpdateMember: boolean;
    canRemoveMember: boolean;
    canCreateInvitation: boolean;
    canCancelInvitation: boolean;
};

export type TournamentTeamType = 'national' | 'club';

export type TournamentTeam = {
    id: number;
    name: string;
    short_name: string | null;
    type: TournamentTeamType;
    type_label: string;
};

export type TournamentMatchStatus =
    | 'scheduled'
    | 'in_progress'
    | 'finished'
    | 'postponed'
    | 'cancelled';

export type TournamentMatch = {
    id: number;
    home_tournament_team_id: number;
    away_tournament_team_id: number;
    home_team_name: string;
    away_team_name: string;
    starts_at: string;
    locks_at: string | null;
    status: TournamentMatchStatus;
    status_label: string;
    venue: string | null;
};

export type TournamentOption = {
    value: string;
    label: string;
};

export type RoleOption = {
    value: TeamRole;
    label: string;
};
