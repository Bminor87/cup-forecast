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
        Schema::create('predictions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('prediction_field_id')->constrained('prediction_fields')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_match_id')->nullable()->constrained('matches')->cascadeOnDelete();
            $table->string('context_key');
            $table->json('value');
            $table->string('status');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'prediction_field_id', 'user_id', 'context_key']);
            $table->index(['prediction_field_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['prediction_field_id', 'context_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
