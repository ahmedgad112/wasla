<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('is_open')->default(true)->after('status')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('restaurants', 'is_open')) {
            return;
        }

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropIndex(['is_open']);
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('is_open');
        });
    }
};
