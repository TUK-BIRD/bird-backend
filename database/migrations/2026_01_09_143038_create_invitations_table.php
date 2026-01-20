<?php

use App\Enums\UserSpaceRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token', 64)->unique();
            $table->enum('user_space_role', array_column(UserSpaceRole::cases(), 'value'))
                ->default(UserSpaceRole::MEMBER->value);

            $table->foreignId('space_id')
                ->constrained('spaces')
                ->cascadeOnDelete();
            $table->foreignId('inviter_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->enum('status', ['PENDING', 'ACCEPTED', 'EXPIRED'])
                ->default('PENDING');
            $table->dateTime('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
