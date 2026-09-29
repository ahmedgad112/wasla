<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('restaurants', 'availability_status')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->string('availability_status', 20)
                    ->default('CLOSED')
                    ->after('status')
                    ->index();
            });
        }

        if (Schema::hasColumn('restaurants', 'is_open')) {
            DB::table('restaurants')->where('is_open', true)->update(['availability_status' => 'OPEN']);
            DB::table('restaurants')->where('is_open', false)->update(['availability_status' => 'CLOSED']);

            // SQLite cannot drop an indexed column in one step.
            Schema::table('restaurants', function (Blueprint $table) {
                $table->dropIndex(['is_open']);
            });

            Schema::table('restaurants', function (Blueprint $table) {
                $table->dropColumn('is_open');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('restaurants', 'is_open')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->boolean('is_open')->default(true)->after('status')->index();
            });
        }

        if (Schema::hasColumn('restaurants', 'availability_status')) {
            DB::table('restaurants')->where('availability_status', 'OPEN')->update(['is_open' => true]);
            DB::table('restaurants')->whereIn('availability_status', ['BUSY', 'CLOSED'])->update(['is_open' => false]);

            Schema::table('restaurants', function (Blueprint $table) {
                $table->dropIndex(['availability_status']);
            });

            Schema::table('restaurants', function (Blueprint $table) {
                $table->dropColumn('availability_status');
            });
        }
    }
};
