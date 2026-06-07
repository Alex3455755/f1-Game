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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name', 10);
            $table->string('color', 7);
            $table->string('logo')->nullable();
            $table->string('base_country');
            $table->integer('overall_rating')->default(70);
            $table->integer('engine_rating')->default(70);
            $table->integer('aero_rating')->default(70);
            $table->integer('reliability_rating')->default(70);
            $table->bigInteger('budget')->default(100000000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
