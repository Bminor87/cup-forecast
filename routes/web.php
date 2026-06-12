<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Tournaments\ParticipantExperienceController;
use App\Http\Controllers\Tournaments\ParticipantPredictionController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', ValidateSessionWithWorkOS::class, EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('predictions', [ParticipantPredictionController::class, 'index'])->name('predictions.index');
        Route::get('predictions/tournament', [ParticipantExperienceController::class, 'tournament'])->name('predictions.tournament');
        Route::get('predictions/matches', [ParticipantExperienceController::class, 'matches'])->name('predictions.matches');
        Route::get('leaderboard', [ParticipantExperienceController::class, 'leaderboard'])->name('predictions.leaderboard');
        Route::get('rules', [ParticipantExperienceController::class, 'rules'])->name('predictions.rules');
        Route::put('predictions/{predictionField}', [ParticipantPredictionController::class, 'upsert'])->name('predictions.upsert');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
