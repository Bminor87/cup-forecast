<?php

use App\Http\Controllers\Tournaments\PlayerController;
use App\Http\Controllers\Tournaments\PredictionFieldController;
use App\Http\Controllers\Tournaments\PredictionResultController;
use App\Http\Controllers\Tournaments\PredictionResultPageController;
use App\Http\Controllers\Tournaments\PredictionScoreController;
use App\Http\Controllers\Tournaments\TournamentAdminPageController;
use App\Http\Controllers\Tournaments\TournamentMatchController;
use App\Http\Controllers\Tournaments\TournamentTeamController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::prefix('{team}/admin')
    ->middleware(['auth', ValidateSessionWithWorkOS::class, EnsureTeamMembership::class])
    ->group(function () {
        // Tournament Teams
        Route::post('teams', [TournamentTeamController::class, 'store'])->name('admin.teams.store');
        Route::delete('teams/{tournamentTeam}', [TournamentTeamController::class, 'destroy'])->name('admin.teams.destroy');
        Route::get('teams', [TournamentAdminPageController::class, 'teams'])->name('admin.teams');

        // Players
        Route::post('players', [PlayerController::class, 'store'])->name('admin.players.store');
        Route::delete('players/{player}', [PlayerController::class, 'destroy'])->name('admin.players.destroy');
        Route::get('players', [TournamentAdminPageController::class, 'players'])->name('admin.players');

        // Matches
        Route::post('matches', [TournamentMatchController::class, 'store'])->name('admin.matches.store');
        Route::delete('matches/{tournamentMatch}', [TournamentMatchController::class, 'destroy'])->name('admin.matches.destroy');
        Route::get('matches', [TournamentAdminPageController::class, 'matches'])->name('admin.matches');

        // Prediction Templates & Questions
        Route::post('prediction-fields', [PredictionFieldController::class, 'store'])->name('admin.prediction-fields.store');
        Route::post('prediction-fields/templates', [PredictionFieldController::class, 'storeTemplate'])->name('admin.prediction-fields.templates.store');
        Route::patch('prediction-fields/{predictionField}', [PredictionFieldController::class, 'update'])->name('admin.prediction-fields.update');
        Route::get('prediction-questions', [TournamentAdminPageController::class, 'predictionQuestions'])->name('admin.prediction-questions');

        // Results
        Route::get('results/tournament', [PredictionResultPageController::class, 'tournament'])->name('admin.prediction-results.tournament');
        Route::get('results/matches', [PredictionResultPageController::class, 'matches'])->name('admin.prediction-results.matches');
        Route::put('results/{predictionField}', [PredictionResultController::class, 'upsert'])->name('admin.prediction-results.upsert');
        Route::post('results/recalculate', [PredictionScoreController::class, 'recalculate'])->name('admin.prediction-scores.recalculate');

        // Participants
        Route::get('participants', [TournamentAdminPageController::class, 'participants'])->name('admin.participants');
    });
