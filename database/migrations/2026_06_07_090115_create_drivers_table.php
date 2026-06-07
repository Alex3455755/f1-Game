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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code', 3);
            $table->integer('number');
            $table->string('nationality');
            $table->integer('pace')->default(70);
            $table->integer('consistency')->default(70);
            $table->integer('wet_skill')->default(70);
            $table->integer('overtaking')->default(70);
            $table->integer('defending')->default(70);
            $table->integer('tire_management')->default(70);
            $table->boolean('is_player_driver')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
