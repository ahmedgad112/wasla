<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if (! Schema::hasColumn('restaurants', 'delivery_provider')) {
                $table->string('delivery_provider', 20)
                    ->default('RESTAURANT')
                    ->after('estimated_delivery_time')
                    ->index();
            }

            if (! Schema::hasColumn('restaurants', 'delivery_enabled')) {
                $table->boolean('delivery_enabled')
                    ->default(true)
                    ->after('delivery_provider')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if (Schema::hasColumn('restaurants', 'delivery_enabled')) {
                $table->dropIndex(['delivery_enabled']);
                $table->dropColumn('delivery_enabled');
            }

            if (Schema::hasColumn('restaurants', 'delivery_provider')) {
                $table->dropIndex(['delivery_provider']);
                $table->dropColumn('delivery_provider');
            }
        });
    }
};
