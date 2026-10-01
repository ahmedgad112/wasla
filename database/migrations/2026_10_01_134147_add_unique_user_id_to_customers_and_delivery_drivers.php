<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->collapseDuplicates('customers', 'user_id', function (int $keepId, int $dropId): void {
            DB::table('customer_addresses')->where('customer_id', $dropId)->update(['customer_id' => $keepId]);
            DB::table('orders')->where('customer_id', $dropId)->update(['customer_id' => $keepId]);
            DB::table('customers')->where('id', $dropId)->delete();
        });

        $this->collapseDuplicates('delivery_drivers', 'user_id', function (int $keepId, int $dropId): void {
            DB::table('orders')->where('assigned_delivery_id', $dropId)->update(['assigned_delivery_id' => $keepId]);
            DB::table('delivery_drivers')->where('id', $dropId)->delete();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('user_id');
        });

        Schema::table('delivery_drivers', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_drivers', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });
    }

    private function collapseDuplicates(string $table, string $column, callable $merge): void
    {
        $duplicateKeys = DB::table($table)
            ->select($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->pluck($column);

        foreach ($duplicateKeys as $key) {
            $ids = DB::table($table)->where($column, $key)->orderBy('id')->pluck('id');
            $keepId = (int) $ids->shift();

            foreach ($ids as $dropId) {
                $merge($keepId, (int) $dropId);
            }
        }
    }
};
