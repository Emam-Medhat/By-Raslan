<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Modules\Identity\Support\AccessRegistry;

return new class extends Migration
{
    public function up(): void
    {
        foreach (AccessRegistry::allLinks() as $link) {
            DB::table('permissions')->updateOrInsert(
                ['id' => $link['permission']],
                ['grp' => 'الوصول للشاشات', 'label' => $link['label']]
            );
        }
    }

    public function down(): void
    {
        DB::table('permission_user')->whereIn('permission_id', AccessRegistry::permissionIds())->delete();
        DB::table('role_permission')->whereIn('permission_id', AccessRegistry::permissionIds())->delete();
        DB::table('permissions')->whereIn('id', AccessRegistry::permissionIds())->delete();
    }
};
