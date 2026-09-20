<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * stripe_customer_id/stripe_subscription_id are placeholders for when
     * billing moves to Stripe - nothing writes them yet. Until then,
     * status/suspended_reason are set manually by a HearthGG super admin
     * (see Client::activate()/suspend()/cancel()).
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('suspended_reason')->nullable()->after('status');
            $table->string('stripe_customer_id')->nullable()->after('trial_ends_at');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['suspended_reason', 'stripe_customer_id', 'stripe_subscription_id']);
        });
    }
};
