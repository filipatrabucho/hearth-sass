<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A cached snapshot of each client's Discord guild members, refreshed
     * by App\Services\Discord\MemberService::sync(). Lets the "membros"
     * module list/search members without hitting the Discord API on
     * every request.
     */
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('discord_user_id');
            $table->string('username');
            $table->string('global_name')->nullable();
            $table->string('avatar_hash')->nullable();
            $table->json('role_ids')->nullable();
            $table->timestamp('joined_discord_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'discord_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
