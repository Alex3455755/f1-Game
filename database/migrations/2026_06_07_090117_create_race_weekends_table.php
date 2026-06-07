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
        Schema::create('race_weekends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->onDelete('cascade');
            $table->foreignId('circuit_id')->constrained()->onDelete('cascade');
            $table->integer('round_number');
            $table->string('race_name');
            $table->date('race_date');
            $table->enum('weather', ['sunny', 'cloudy', 'rain', 'heavy_rain'])->default('sunny');
            $table->enum('status', ['upcoming', 'setup', 'qualifying', 'race', 'completed'])->default('upcoming');
            $table->boolean('is_sprint')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('race_weekends');
    }
};
