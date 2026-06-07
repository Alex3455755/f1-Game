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
        Schema::create('race_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_weekend_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->integer('grid_position');
            $table->integer('finish_position')->nullable();
            $table->enum('status', ['running', 'finished', 'dnf', 'dsq'])->default('running');
            $table->string('tire_compound')->default('medium');
            $table->integer('current_lap')->default(0);
            $table->integer('pit_stop_count')->default(0);
            $table->decimal('gap_to_leader', 8, 3)->default(0);
            $table->integer('points_scored')->default(0);
            $table->boolean('fastest_lap')->default(false);
            $table->integer('last_pit_lap')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('race_results');
    }
};
