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
        Schema::create('prediction_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('prediction_id')->constrained('predictions')->cascadeOnDelete();
            $table->foreignId('prediction_field_id')->constrained('prediction_fields')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('strategy_key');
            $table->integer('points');
            $table->integer('max_points');
            $table->json('breakdown')->nullable();
            $table->timestamp('scored_at');
            $table->timestamps();

            $table->unique('prediction_id');
            $table->index(['tournament_id', 'user_id']);
            $table->index(['prediction_field_id', 'points']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediction_scores');
    }
};
