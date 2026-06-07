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
        Schema::create('radio_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_weekend_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->integer('lap');
            $table->enum('direction', ['engineer_to_driver', 'driver_to_engineer']);
            $table->string('message_type');
            $table->text('message');
            $table->boolean('is_player_sent')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radio_messages');
    }
};
