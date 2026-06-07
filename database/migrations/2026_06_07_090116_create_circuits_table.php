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
        Schema::create('circuits', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country');
            $table->string('city');
            $table->integer('lap_count');
            $table->decimal('lap_length', 5, 3);
            $table->enum('circuit_type', ['street', 'permanent', 'mixed'])->default('permanent');
            $table->integer('downforce_requirement')->default(50);
            $table->integer('tire_wear_rate')->default(50);
            $table->integer('fuel_consumption')->default(50);
            $table->integer('overtaking_difficulty')->default(50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circuits');
    }
};
