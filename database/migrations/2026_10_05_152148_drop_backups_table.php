<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('backups');

        $permission = Permission::query()
            ->where('name', 'backups.manage')
            ->where('guard_name', 'web')
            ->first();

        if ($permission !== null) {
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        User::query()
            ->whereIn('role', ['SUPER_ADMIN', 'ADMIN'])
            ->pluck('id')
            ->each(function (int $id): void {
                cache()->forget("user.{$id}.permissions");
            });

        if (Storage::disk('local')->directoryExists('backups')) {
            Storage::disk('local')->deleteDirectory('backups');
        }
    }

    public function down(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('type')->default('DATABASE');
            $table->bigInteger('file_size')->default(0);
            $table->string('status')->default('SUCCESS');
            $table->string('storage_location')->default('LOCAL');
            $table->text('error_message')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $permission = Permission::findOrCreate('backups.manage', 'web');

        foreach (['SUPER_ADMIN', 'ADMIN'] as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');

            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
