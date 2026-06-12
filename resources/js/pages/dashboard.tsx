import { Form, Head, router } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import teamMatchesRoutes from '@/routes/teams/matches';
import predictionFieldsRoutes from '@/routes/teams/prediction-fields';
import predictionResultsRoutes from '@/routes/teams/prediction-results';
import tournamentTeamsRoutes from '@/routes/teams/tournament-teams';
import type {
    DashboardInvitation,
    PredictionField,
    PredictionResultStatus,
    TournamentMatch,
    TournamentOption,
    TournamentTeam,
} from '@/types';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    currentTeamSlug: string | null;
    tournamentTeams: TournamentTeam[];
    matches: TournamentMatch[];
    predictionFields: PredictionField[];
    teamTypes: TournamentOption[];
    matchStatuses: TournamentOption[];
    predictionScopes: TournamentOption[];
    predictionFieldTypes: TournamentOption[];
    predictionVisibilities: TournamentOption[];
    predictionResultStatuses: TournamentOption[];
    canManageTournamentTeams: boolean;
    canManageMatches: boolean;
    canManagePredictionFields: boolean;
    canResolvePredictionResults: boolean;
};

export default function Dashboard({
    pendingInvitations = [],
    currentTeamSlug,
    tournamentTeams,
    matches,
    predictionFields,
    teamTypes,
    matchStatuses,
    predictionScopes,
    predictionFieldTypes,
    predictionVisibilities,
    predictionResultStatuses,
    canManageTournamentTeams,
    canManageMatches,
    canManagePredictionFields,
    canResolvePredictionResults,
}: Props) {
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    const tournamentTeamsStoreUrl = currentTeamSlug
        ? tournamentTeamsRoutes.store.url(currentTeamSlug)
        : '#';
    const matchesStoreUrl = currentTeamSlug
        ? teamMatchesRoutes.store.url(currentTeamSlug)
        : '#';
    const predictionFieldsStoreUrl = currentTeamSlug
        ? predictionFieldsRoutes.store.url(currentTeamSlug)
        : '#';

    const deleteTournamentTeam = (tournamentTeamId: number) => {
        if (!currentTeamSlug) {
            return;
        }

        router.delete(
            tournamentTeamsRoutes.destroy.url([currentTeamSlug, tournamentTeamId]),
            {
                preserveScroll: true,
            },
        );
    };

    const deleteMatch = (matchId: number) => {
        if (!currentTeamSlug) {
            return;
        }

        router.delete(teamMatchesRoutes.destroy.url([currentTeamSlug, matchId]), {
            preserveScroll: true,
        });
    };

    const predictionResultDefaultStatus =
        predictionResultStatuses.find((status) => status.value === 'resolved')
            ?.value ?? predictionResultStatuses[0]?.value;

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />
            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto rounded-xl p-4">
                <div className="space-y-6 rounded-xl border p-5">
                    <Heading
                        variant="small"
                        title="Tournament teams"
                        description="Temporary testing controls for managing teams"
                    />

                    {canManageTournamentTeams && currentTeamSlug ? (
                        <Form
                            action={tournamentTeamsStoreUrl}
                            method="post"
                            className="grid gap-4 md:grid-cols-4"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="tournament-team-name">
                                            Team name
                                        </Label>
                                        <Input
                                            id="tournament-team-name"
                                            name="name"
                                            required
                                            data-test="tournament-team-name-input"
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="tournament-team-short-name">
                                            Short name
                                        </Label>
                                        <Input
                                            id="tournament-team-short-name"
                                            name="short_name"
                                            maxLength={16}
                                            data-test="tournament-team-short-name-input"
                                        />
                                        <InputError message={errors.short_name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="tournament-team-type">
                                            Type
                                        </Label>
                                        <select
                                            id="tournament-team-type"
                                            name="type"
                                            className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                            defaultValue={teamTypes[0]?.value}
                                            data-test="tournament-team-type-input"
                                        >
                                            {teamTypes.map((type) => (
                                                <option key={type.value} value={type.value}>
                                                    {type.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.type} />
                                    </div>

                                    <div className="md:col-span-4">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            data-test="tournament-team-create-button"
                                        >
                                            Add tournament team
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    ) : null}

                    <div className="space-y-3">
                        {tournamentTeams.map((tournamentTeam) => (
                            <div
                                key={tournamentTeam.id}
                                className="flex items-center justify-between rounded-lg border p-4"
                                data-test="tournament-team-row"
                            >
                                <div>
                                    <div className="font-medium">
                                        {tournamentTeam.name}
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        {tournamentTeam.type_label}
                                        {tournamentTeam.short_name
                                            ? ` • ${tournamentTeam.short_name}`
                                            : ''}
                                    </div>
                                </div>

                                {canManageTournamentTeams ? (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            deleteTournamentTeam(
                                                tournamentTeam.id,
                                            )
                                        }
                                        data-test="tournament-team-delete-button"
                                    >
                                        <X className="h-4 w-4" />
                                    </Button>
                                ) : null}
                            </div>
                        ))}
                    </div>
                </div>

                <div className="space-y-6 rounded-xl border p-5">
                    <Heading
                        variant="small"
                        title="Matches"
                        description="Temporary testing controls for tournament fixtures"
                    />

                    {canManageMatches && currentTeamSlug && tournamentTeams.length >= 2 ? (
                        <Form
                            action={matchesStoreUrl}
                            method="post"
                            className="grid gap-4 md:grid-cols-3"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="home-team">Home team</Label>
                                        <select
                                            id="home-team"
                                            name="home_tournament_team_id"
                                            className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                            defaultValue={tournamentTeams[0]?.id}
                                            data-test="match-home-team-input"
                                        >
                                            {tournamentTeams.map((tournamentTeam) => (
                                                <option
                                                    key={tournamentTeam.id}
                                                    value={tournamentTeam.id}
                                                >
                                                    {tournamentTeam.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.home_tournament_team_id} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="away-team">Away team</Label>
                                        <select
                                            id="away-team"
                                            name="away_tournament_team_id"
                                            className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                            defaultValue={tournamentTeams[1]?.id ?? tournamentTeams[0]?.id}
                                            data-test="match-away-team-input"
                                        >
                                            {tournamentTeams.map((tournamentTeam) => (
                                                <option
                                                    key={tournamentTeam.id}
                                                    value={tournamentTeam.id}
                                                >
                                                    {tournamentTeam.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.away_tournament_team_id} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="match-status">Status</Label>
                                        <select
                                            id="match-status"
                                            name="status"
                                            className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                            defaultValue="scheduled"
                                            data-test="match-status-input"
                                        >
                                            {matchStatuses.map((status) => (
                                                <option key={status.value} value={status.value}>
                                                    {status.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.status} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="starts-at">Starts at</Label>
                                        <Input
                                            id="starts-at"
                                            name="starts_at"
                                            type="datetime-local"
                                            required
                                            data-test="match-starts-at-input"
                                        />
                                        <InputError message={errors.starts_at} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="locks-at">Locks at</Label>
                                        <Input
                                            id="locks-at"
                                            name="locks_at"
                                            type="datetime-local"
                                            data-test="match-locks-at-input"
                                        />
                                        <InputError message={errors.locks_at} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="venue">Venue</Label>
                                        <Input
                                            id="venue"
                                            name="venue"
                                            data-test="match-venue-input"
                                        />
                                        <InputError message={errors.venue} />
                                    </div>

                                    <div className="md:col-span-3">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            data-test="match-create-button"
                                        >
                                            Add match
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    ) : null}

                    <div className="space-y-3">
                        {matches.map((match) => (
                            <div
                                key={match.id}
                                className="flex items-center justify-between rounded-lg border p-4"
                                data-test="match-row"
                            >
                                <div>
                                    <div className="font-medium">
                                        {match.home_team_name} vs {match.away_team_name}
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        {new Date(match.starts_at).toLocaleString()} •{' '}
                                        {match.status_label}
                                        {match.venue ? ` • ${match.venue}` : ''}
                                    </div>
                                </div>

                                {canManageMatches ? (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => deleteMatch(match.id)}
                                        data-test="match-delete-button"
                                    >
                                        <X className="h-4 w-4" />
                                    </Button>
                                ) : null}
                            </div>
                        ))}
                    </div>
                </div>

                <div className="space-y-6 rounded-xl border p-5">
                    <Heading
                        variant="small"
                        title="Prediction fields"
                        description="Minimal admin tools for Phase 5 field setup"
                    />

                    {canManagePredictionFields && currentTeamSlug ? (
                        <Form
                            action={predictionFieldsStoreUrl}
                            method="post"
                            className="grid gap-4 md:grid-cols-3"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="prediction-field-label">Label</Label>
                                        <Input
                                            id="prediction-field-label"
                                            name="label"
                                            required
                                            data-test="prediction-field-label-input"
                                        />
                                        <InputError message={errors.label} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="prediction-field-key">Key</Label>
                                        <Input
                                            id="prediction-field-key"
                                            name="key"
                                            required
                                            data-test="prediction-field-key-input"
                                        />
                                        <InputError message={errors.key} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="prediction-field-description">
                                            Description
                                        </Label>
                                        <Input
                                            id="prediction-field-description"
                                            name="description"
                                            data-test="prediction-field-description-input"
                                        />
                                        <InputError message={errors.description} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="prediction-field-scope">Scope</Label>
                                        <select
                                            id="prediction-field-scope"
                                            name="scope"
                                            className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                            defaultValue={predictionScopes[0]?.value}
                                            data-test="prediction-field-scope-input"
                                        >
                                            {predictionScopes.map((scope) => (
                                                <option key={scope.value} value={scope.value}>
                                                    {scope.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.scope} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="prediction-field-type">Field type</Label>
                                        <select
                                            id="prediction-field-type"
                                            name="field_type"
                                            className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                            defaultValue={predictionFieldTypes[0]?.value}
                                            data-test="prediction-field-type-input"
                                        >
                                            {predictionFieldTypes.map((fieldType) => (
                                                <option key={fieldType.value} value={fieldType.value}>
                                                    {fieldType.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.field_type} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="prediction-field-visibility">Visibility</Label>
                                        <select
                                            id="prediction-field-visibility"
                                            name="visibility"
                                            className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                            defaultValue={predictionVisibilities[0]?.value}
                                            data-test="prediction-field-visibility-input"
                                        >
                                            {predictionVisibilities.map((visibility) => (
                                                <option key={visibility.value} value={visibility.value}>
                                                    {visibility.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.visibility} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="prediction-field-strategy">
                                            Scoring strategy key
                                        </Label>
                                        <Input
                                            id="prediction-field-strategy"
                                            name="scoring_strategy_key"
                                            defaultValue="exact_match"
                                            required
                                            data-test="prediction-field-strategy-input"
                                        />
                                        <InputError message={errors.scoring_strategy_key} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="prediction-field-schema">
                                            Validation schema JSON
                                        </Label>
                                        <Input
                                            id="prediction-field-schema"
                                            name="validation_schema"
                                            defaultValue='{"required":true}'
                                            data-test="prediction-field-schema-input"
                                        />
                                        <InputError message={errors.validation_schema} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="prediction-field-config">
                                            Configuration JSON
                                        </Label>
                                        <Input
                                            id="prediction-field-config"
                                            name="configuration"
                                            defaultValue='{"is_locked":false,"max_points":1}'
                                            data-test="prediction-field-config-input"
                                        />
                                        <InputError message={errors.configuration} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="prediction-field-active">Active</Label>
                                        <select
                                            id="prediction-field-active"
                                            name="is_active"
                                            className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                            defaultValue="1"
                                        >
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                        <InputError message={errors.is_active} />
                                    </div>

                                    <div className="md:col-span-3">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            data-test="prediction-field-create-button"
                                        >
                                            Create prediction field
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    ) : null}

                    <div className="space-y-4">
                        {predictionFields.map((field) => (
                            <div key={field.id} className="rounded-lg border p-4" data-test="prediction-field-row">
                                <Form
                                    action={predictionFieldsRoutes.update.url([
                                        currentTeamSlug ?? '',
                                        field.id,
                                    ])}
                                    method="patch"
                                    className="grid gap-3 md:grid-cols-3"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label>Label</Label>
                                                <Input name="label" defaultValue={field.label} required />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Key</Label>
                                                <Input name="key" defaultValue={field.key} required />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Description</Label>
                                                <Input name="description" defaultValue={field.description ?? ''} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Scope</Label>
                                                <select
                                                    name="scope"
                                                    className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                                    defaultValue={field.scope}
                                                >
                                                    {predictionScopes.map((scope) => (
                                                        <option key={scope.value} value={scope.value}>
                                                            {scope.label}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Field type</Label>
                                                <select
                                                    name="field_type"
                                                    className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                                    defaultValue={field.field_type}
                                                >
                                                    {predictionFieldTypes.map((fieldType) => (
                                                        <option key={fieldType.value} value={fieldType.value}>
                                                            {fieldType.label}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Visibility</Label>
                                                <select
                                                    name="visibility"
                                                    className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                                    defaultValue={field.visibility}
                                                >
                                                    {predictionVisibilities.map((visibility) => (
                                                        <option key={visibility.value} value={visibility.value}>
                                                            {visibility.label}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Scoring strategy</Label>
                                                <Input
                                                    name="scoring_strategy_key"
                                                    defaultValue={field.scoring_strategy_key}
                                                    required
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Validation schema JSON</Label>
                                                <Input
                                                    name="validation_schema"
                                                    defaultValue={JSON.stringify(field.validation_schema ?? {})}
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Configuration JSON</Label>
                                                <Input
                                                    name="configuration"
                                                    defaultValue={JSON.stringify(field.configuration ?? {})}
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Active</Label>
                                                <select
                                                    name="is_active"
                                                    className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                                    defaultValue={field.is_active ? '1' : '0'}
                                                >
                                                    <option value="1">Yes</option>
                                                    <option value="0">No</option>
                                                </select>
                                            </div>
                                            <div className="md:col-span-3 flex items-center justify-between">
                                                <div className="text-sm text-muted-foreground">
                                                    {field.scope === 'match'
                                                        ? 'Match scoped reusable template'
                                                        : 'Tournament scoped'}
                                                </div>
                                                {canManagePredictionFields ? (
                                                    <Button
                                                        type="submit"
                                                        size="sm"
                                                        disabled={processing}
                                                        data-test="prediction-field-update-button"
                                                    >
                                                        Save field
                                                    </Button>
                                                ) : null}
                                            </div>
                                            <InputError message={errors.label} />
                                        </>
                                    )}
                                </Form>

                                {canResolvePredictionResults && currentTeamSlug ? (
                                    <Form
                                        action={predictionResultsRoutes.upsert.url([
                                            currentTeamSlug,
                                            field.id,
                                        ])}
                                        method="put"
                                        className="mt-4 grid gap-3 md:grid-cols-3"
                                    >
                                        {({ errors, processing }) => (
                                            <>
                                                {field.scope === 'match' ? (
                                                    <div className="grid gap-2">
                                                        <Label>Match</Label>
                                                        <select
                                                            name="tournament_match_id"
                                                            className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                                            defaultValue={matches[0]?.id}
                                                            data-test="prediction-result-match-input"
                                                        >
                                                            {matches.map((match) => (
                                                                <option key={match.id} value={match.id}>
                                                                    {match.home_team_name} vs {match.away_team_name}
                                                                </option>
                                                            ))}
                                                        </select>
                                                        <InputError message={errors.tournament_match_id} />
                                                    </div>
                                                ) : null}
                                                <div className="grid gap-2 md:col-span-2">
                                                    <Label>Result JSON</Label>
                                                    <Input
                                                        name="result_value"
                                                        defaultValue={JSON.stringify(field.results[0]?.value ?? {})}
                                                        data-test="prediction-result-value-input"
                                                    />
                                                    <InputError message={errors.value} />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Status</Label>
                                                    <select
                                                        name="status"
                                                        className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                                                        defaultValue={
                                                            (field.results[0]?.status as PredictionResultStatus | undefined) ??
                                                            predictionResultDefaultStatus
                                                        }
                                                        data-test="prediction-result-status-input"
                                                    >
                                                        {predictionResultStatuses.map((status) => (
                                                            <option key={status.value} value={status.value}>
                                                                {status.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                                <div className="md:col-span-3 flex items-center justify-between">
                                                    <div className="text-sm text-muted-foreground">
                                                        {field.results.length > 0
                                                            ? `${field.results.length} official result${field.results.length === 1 ? '' : 's'} saved`
                                                            : 'No official result yet'}
                                                    </div>
                                                    <Button
                                                        type="submit"
                                                        size="sm"
                                                        disabled={processing}
                                                        data-test="prediction-result-resolve-button"
                                                    >
                                                        Save result
                                                    </Button>
                                                </div>
                                            </>
                                        )}
                                    </Form>
                                ) : null}

                                {field.results.length > 0 ? (
                                    <div className="mt-4 space-y-2 text-sm text-muted-foreground">
                                        {field.results.map((result) => (
                                            <div key={result.id} className="rounded-md border px-3 py-2">
                                                <div>
                                                    {result.match_name ?? 'Tournament result'}
                                                </div>
                                                <div>
                                                    {JSON.stringify(result.value)}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : null}
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
