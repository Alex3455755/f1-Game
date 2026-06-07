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
        Schema::create('pit_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_weekend_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->integer('lap');
            $table->enum('compound_in', ['soft', 'medium', 'hard', 'intermediate', 'wet']);
            $table->enum('compound_out', ['soft', 'medium', 'hard', 'intermediate', 'wet']);
            $table->decimal('duration', 5, 3)->nullable();
            $table->boolean('is_player_decision')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pit_stops');
    }
};
