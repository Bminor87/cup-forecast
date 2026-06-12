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
        Schema::create('tournament_teams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name');
            $table->string('short_name', 16)->nullable();
            $table->string('type');
            $table->string('external_ref')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'name']);
            $table->index(['tournament_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_teams');
    }
};
