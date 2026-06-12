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
        Schema::create('prediction_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained('teams')->cascadeOnDelete();
            $table->string('scope');
            $table->string('field_type');
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('key');
            $table->string('visibility');
            $table->json('validation_schema')->nullable();
            $table->string('scoring_strategy_key');
            $table->json('configuration')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tournament_id', 'key']);
            $table->index(['tournament_id', 'scope']);
            $table->index(['tournament_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediction_fields');
    }
};
