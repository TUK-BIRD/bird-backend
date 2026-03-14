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
        Schema::create('ble_scan_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anchor_id')->nullable()->constrained('ble_anchors')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->char('device_mac', 17);
            $table->string('device_name', 100)->nullable();
            $table->smallInteger('rssi_dbm');
            $table->dateTime('scanned_at', 3);
            $table->dateTime('received_at', 3)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['anchor_id', 'scanned_at']);
            $table->index(['room_id', 'scanned_at']);
            $table->index(['device_mac', 'scanned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ble_scan_events');
    }
};
