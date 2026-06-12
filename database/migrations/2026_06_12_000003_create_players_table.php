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
        Schema::create('players', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('tournament_team_id')->constrained('tournament_teams')->cascadeOnDelete();
            $table->string('name');
            $table->string('short_name', 64)->nullable();
            $table->unsignedSmallInteger('shirt_number')->nullable();
            $table->string('position')->nullable();
            $table->string('external_ref')->nullable();
            $table->string('image_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tournament_id', 'tournament_team_id']);
            $table->index(['tournament_id', 'position']);
            $table->unique(['tournament_team_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
