<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records that the HearthGG Discord bot was invited into a client's
     * guild and with which permissions, so we know we can actually call
     * the Discord API on their behalf. The bot itself authenticates with
     * a single static token (config('services.discord.bot_token')); this
     * is just bookkeeping of the per-guild install, not a credential.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('bot_permissions')->nullable()->after('icon_hash');
            $table->timestamp('bot_installed_at')->nullable()->after('bot_permissions');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['bot_permissions', 'bot_installed_at']);
        });
    }
};
