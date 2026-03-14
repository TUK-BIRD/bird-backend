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
        Schema::table('ble_scan_events', function (Blueprint $table) {
            $table->boolean('is_inside')->nullable()->after('rssi_dbm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ble_scan_events', function (Blueprint $table) {
            $table->dropColumn('is_inside');
        });
    }
};
