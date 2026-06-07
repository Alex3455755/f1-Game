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
        Schema::create('qualifying_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_weekend_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->integer('position')->nullable();
            $table->decimal('q1_time', 8, 3)->nullable();
            $table->decimal('q2_time', 8, 3)->nullable();
            $table->decimal('q3_time', 8, 3)->nullable();
            $table->boolean('q1_eliminated')->default(false);
            $table->boolean('q2_eliminated')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qualifying_results');
    }
};
