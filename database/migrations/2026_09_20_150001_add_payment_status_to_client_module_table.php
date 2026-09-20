<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * is_enabled is a manual on/off switch; payment_status/paid_until track
     * why it's on, so we know whether a client still has paid access to a
     * module rather than just whether someone flipped a switch once.
     */
    public function up(): void
    {
        Schema::table('client_module', function (Blueprint $table) {
            $table->string('payment_status')->default('trialing')->after('is_enabled');
            $table->timestamp('paid_until')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('client_module', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'paid_until']);
        });
    }
};
