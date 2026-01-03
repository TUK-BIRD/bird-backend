<?php

use App\Enums\UserSpaceRole;
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
        Schema::create('user_spaces', function (Blueprint $table) {
            $table->id();
            $table->enum('role', UserSpaceRole::cases())->default(UserSpaceRole::OWNER);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('space_id')->constrained('spaces')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_spaces');
    }
};
