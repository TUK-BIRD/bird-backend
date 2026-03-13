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
        Schema::create('scan_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->enum('reported_state', ['on', 'off']);
            $table->string('request_id', 64)->nullable();
            $table->boolean('ok')->nullable();
            $table->timestamp('reported_at')->useCurrent();
            $table->timestamps();
            $table->index(['room_id', 'reported_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_status_events');
    }
};
