<?php

declare(strict_types=1);

use App\MoonShine\Resources\GlobalAdminActivityResource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('moonshine_user_permissions')
            ->orderBy('id')
            ->get(['id', 'permissions'])
            ->each(function (object $row): void {
                $permissions = json_decode((string) $row->permissions, true);

                if (! is_array($permissions)) {
                    return;
                }

                $permissions[GlobalAdminActivityResource::class] = $this->abilities();

                DB::table('moonshine_user_permissions')
                    ->where('id', $row->id)
                    ->update([
                        'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('moonshine_user_permissions')
            ->orderBy('id')
            ->get(['id', 'permissions'])
            ->each(function (object $row): void {
                $permissions = json_decode((string) $row->permissions, true);

                if (! is_array($permissions)) {
                    return;
                }

                unset($permissions[GlobalAdminActivityResource::class]);

                DB::table('moonshine_user_permissions')
                    ->where('id', $row->id)
                    ->update([
                        'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ]);
            });
    }

    /**
     * @return array<string, bool>
     */
    private function abilities(): array
    {
        return [
            'viewAny' => true,
            'view' => true,
            'create' => false,
            'update' => false,
            'delete' => false,
            'massDelete' => false,
            'restore' => false,
            'forceDelete' => false,
        ];
    }
};
