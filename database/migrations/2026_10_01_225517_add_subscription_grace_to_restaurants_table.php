<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->unsignedSmallInteger('grace_period_days')->nullable()->after('billing_cycle');
            $table->date('subscription_starts_at')->nullable()->after('grace_period_days');
            $table->date('subscription_ends_at')->nullable()->after('subscription_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn([
                'grace_period_days',
                'subscription_starts_at',
                'subscription_ends_at',
            ]);
        });
    }
};
