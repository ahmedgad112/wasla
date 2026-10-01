<?php

use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PermissionCatalog::syncDefaults(overwriteExistingGrants: false);
    }

    public function down(): void
    {
        // Grants stay in place. Rolling them back would lock live accounts out,
        // and the next migrate only fills roles that still have no permissions.
    }
};
