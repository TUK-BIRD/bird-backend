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
        Schema::table('user_spaces', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['space_id']);
        });

        Schema::rename('user_spaces', 'space_user');

        Schema::table('space_user', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('space_id')->references('id')->on('spaces')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('space_user', 'user_spaces');
    }
};
