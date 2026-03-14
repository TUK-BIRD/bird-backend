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
        Schema::create('ble_anchors', function (Blueprint $table) {
            $table->id();
            $table->string('anchor_uid', 64)->unique();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50);
            $table->tinyInteger('tx_power_dbm')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();

            $table->index(['room_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ble_anchors');
    }
};
