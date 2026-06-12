<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Teams\TeamController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Teams\TeamMemberController;
use App\Http\Controllers\Tournaments\PredictionFieldController;
use App\Http\Controllers\Tournaments\PredictionResultController;
use App\Http\Controllers\Tournaments\TournamentMatchController;
use App\Http\Controllers\Tournaments\TournamentTeamController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::middleware([
    'auth',
    ValidateSessionWithWorkOS::class,
])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::get('settings/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('settings/teams', [TeamController::class, 'store'])->name('teams.store');

    Route::middleware(EnsureTeamMembership::class)->group(function () {
        Route::get('settings/teams/{team}', [TeamController::class, 'edit'])->name('teams.edit');
        Route::patch('settings/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
        Route::delete('settings/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
        Route::post('settings/teams/{team}/switch', [TeamController::class, 'switch'])->name('teams.switch');

        Route::patch('settings/teams/{team}/members/{user}', [TeamMemberController::class, 'update'])->name('teams.members.update');
        Route::delete('settings/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('teams.members.destroy');

        Route::post('settings/teams/{team}/invitations', [TeamInvitationController::class, 'store'])->name('teams.invitations.store');
        Route::delete('settings/teams/{team}/invitations/{invitation}', [TeamInvitationController::class, 'destroy'])->name('teams.invitations.destroy');

        Route::post('settings/teams/{team}/tournament-teams', [TournamentTeamController::class, 'store'])->name('teams.tournament-teams.store');
        Route::delete('settings/teams/{team}/tournament-teams/{tournamentTeam}', [TournamentTeamController::class, 'destroy'])->name('teams.tournament-teams.destroy');

        Route::post('settings/teams/{team}/matches', [TournamentMatchController::class, 'store'])->name('teams.matches.store');
        Route::delete('settings/teams/{team}/matches/{tournamentMatch}', [TournamentMatchController::class, 'destroy'])->name('teams.matches.destroy');

        Route::post('settings/teams/{team}/prediction-fields', [PredictionFieldController::class, 'store'])->name('teams.prediction-fields.store');
        Route::patch('settings/teams/{team}/prediction-fields/{predictionField}', [PredictionFieldController::class, 'update'])->name('teams.prediction-fields.update');
        Route::put('settings/teams/{team}/prediction-fields/{predictionField}/result', [PredictionResultController::class, 'upsert'])->name('teams.prediction-results.upsert');
    });
});
