<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->char('device_mac', 17);
            $table->string('device_name', 100)->nullable();
            $table->unsignedSmallInteger('matched_anchor_count');
            $table->json('signals');
            $table->json('estimate');
            $table->double('x')->nullable();
            $table->double('y')->nullable();
            $table->double('confidence')->nullable();
            $table->boolean('is_outside')->nullable();
            $table->double('min_distance')->nullable();
            $table->timestamp('window_since');
            $table->timestamp('window_until');
            $table->timestamp('estimated_at');
            $table->timestamps();

            $table->index(['room_id', 'estimated_at']);
            $table->index(['device_mac', 'estimated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_estimates');
    }
};
