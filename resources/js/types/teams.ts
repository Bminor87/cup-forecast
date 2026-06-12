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

export type PredictionFieldScope = 'tournament' | 'match';

export type PredictionFieldType =
    | 'team_picker'
    | 'player_picker'
    | 'text'
    | 'number'
    | 'boolean'
    | 'date'
    | 'time';

export type PredictionOptionSource =
    | 'all_tournament_teams'
    | 'match_teams'
    | 'all_tournament_players'
    | 'match_players'
    | 'static_options';

export type PredictionVisibility =
    | 'hidden_until_lock'
    | 'hidden_until_result'
    | 'always_visible';

export type PredictionResultStatus = 'pending' | 'resolved';
export type PredictionStatus = 'draft' | 'submitted' | 'locked';

export type PredictionPickerOption = {
    value: string | number | boolean;
    label: string;
};

export type ParticipantPredictionField = {
    id: number;
    scope: PredictionFieldScope;
    field_type: PredictionFieldType;
    option_source: PredictionOptionSource | null;
    options: PredictionPickerOption[];
    label: string;
    description: string | null;
    visibility: PredictionVisibility;
    validation_schema: Record<string, unknown> | null;
    context_key: string;
    tournament_match_id: number | null;
    value: unknown;
    status: PredictionStatus;
    is_locked: boolean;
    last_saved_at: string | null;
    result_status: PredictionResultStatus | null;
    result_value: unknown;
    result_is_visible: boolean;
};

export type MatchPredictionGroup = {
    match: {
        id: number;
        name: string;
        starts_at: string;
        locks_at: string | null;
    };
    fields: ParticipantPredictionField[];
};

export type PredictionResult = {
    id: number;
    tournament_match_id: number | null;
    match_name: string | null;
    status: PredictionResultStatus;
    value: Record<string, unknown>;
    resolved_at: string | null;
};

export type PredictionField = {
    id: number;
    scope: PredictionFieldScope;
    field_type: PredictionFieldType;
    option_source?: PredictionOptionSource | null;
    label: string;
    description: string | null;
    key: string;
    visibility: PredictionVisibility;
    validation_schema: Record<string, unknown> | null;
    scoring_strategy_key: string;
    configuration: Record<string, unknown> | null;
    is_active: boolean;
    results: PredictionResult[];
};

export type RoleOption = {
    value: TeamRole;
    label: string;
};
