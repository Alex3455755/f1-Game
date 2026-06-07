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
        Schema::create('car_setups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_weekend_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->integer('front_wing')->default(5)->comment('1-11');
            $table->integer('rear_wing')->default(5)->comment('1-11');
            $table->integer('suspension_stiffness')->default(5)->comment('1-10');
            $table->integer('ride_height')->default(5)->comment('1-10');
            $table->integer('brake_bias')->default(55)->comment('45-65');
            $table->decimal('tire_pressure_front', 4, 1)->default(23.0);
            $table->decimal('tire_pressure_rear', 4, 1)->default(21.5);
            $table->integer('differential')->default(50)->comment('30-70');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_setups');
    }
};
