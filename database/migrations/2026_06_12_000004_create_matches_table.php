<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('home_tournament_team_id')->constrained('tournament_teams')->cascadeOnDelete();
            $table->foreignId('away_tournament_team_id')->constrained('tournament_teams')->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('locks_at')->nullable();
            $table->string('status');
            $table->string('venue')->nullable();
            $table->smallInteger('home_score')->nullable();
            $table->smallInteger('away_score')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tournament_id', 'starts_at']);
            $table->index(['tournament_id', 'status']);
            $table->index(['home_tournament_team_id', 'away_tournament_team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
