<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ble_anchors', function (Blueprint $table) {
            $table->string('health_status', 32)->nullable()->after('installed_at');
            $table->timestamp('health_last_payload_at')->nullable()->after('health_status');
            $table->string('health_last_topic')->nullable()->after('health_last_payload_at');
            $table->unsignedInteger('health_uptime_sec')->nullable()->after('health_last_topic');
            $table->unsignedInteger('health_free_heap')->nullable()->after('health_uptime_sec');
            $table->unsignedInteger('health_min_free_heap')->nullable()->after('health_free_heap');
            $table->boolean('health_wifi_connected')->nullable()->after('health_min_free_heap');
            $table->boolean('health_mqtt_connected')->nullable()->after('health_wifi_connected');
            $table->boolean('health_scan_enabled')->nullable()->after('health_mqtt_connected');
            $table->json('health_raw_payload')->nullable()->after('health_scan_enabled');

            $table->index(['health_status', 'health_last_payload_at'], 'ble_anchors_health_status_payload_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ble_anchors', function (Blueprint $table) {
            $table->dropIndex('ble_anchors_health_status_payload_idx');
            $table->dropColumn([
                'health_status',
                'health_last_payload_at',
                'health_last_topic',
                'health_uptime_sec',
                'health_free_heap',
                'health_min_free_heap',
                'health_wifi_connected',
                'health_mqtt_connected',
                'health_scan_enabled',
                'health_raw_payload',
            ]);
        });
    }
};
