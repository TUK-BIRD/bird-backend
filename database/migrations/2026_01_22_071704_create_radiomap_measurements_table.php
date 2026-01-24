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
        Schema::create('radiomap_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radiomap_session_id')->constrained('radiomap_sessions')->cascadeOnDelete();
            $table->foreignId('reference_point_id')->constrained('reference_points')->cascadeOnDelete();
            $table->foreignId('anchor_id')->constrained('ble_anchors')->cascadeOnDelete();
            $table->tinyInteger('rssi_dbm');
            $table->dateTime('measured_at');
            $table->timestamps();

            $table->index(['radiomap_session_id']);
            $table->index(['reference_point_id', 'anchor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radiomap_measurements');
    }
};
