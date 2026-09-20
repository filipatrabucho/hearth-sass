<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sign-ups from the marketing site's "get started" form - not
     * onboarded clients yet, just a lead for HearthGG to follow up on.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('discord_username')->nullable();
            $table->string('server_name')->nullable();
            $table->string('plan_interest')->default('free');
            $table->text('message')->nullable();
            $table->string('status')->default('new');
            $table->string('source')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
