<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        DB::table('moonshine_user_roles')->updateOrInsert(
            ['name' => 'News editor'],
            [
                'updated_at' => $timestamp,
                'created_at' => $timestamp,
            ]
        );

        $adminId = DB::table('moonshine_users')
            ->where('email', 'admin@itcapital.top')
            ->value('id');

        if ($adminId === null) {
            return;
        }

        $permissions = [
            App\MoonShine\Resources\SummaryResource::class => $this->abilities(),
            App\MoonShine\Resources\UserResource::class => $this->abilities(),
            App\MoonShine\Resources\DepositResource::class => $this->abilities(),
            App\MoonShine\Resources\WithdrawResource::class => $this->abilities(),
            App\MoonShine\Resources\ItcPackageResource::class => $this->abilities(),
            App\MoonShine\Resources\VerifyingUserResource::class => $this->abilities(),
            App\MoonShine\Resources\ItcStakingResource::class => $this->abilities(),
            App\MoonShine\Resources\NewsResource::class => $this->abilities(),
            App\MoonShine\Resources\AdminUserResource::class => $this->abilities(),
            MoonShine\Resources\MoonShineUserRoleResource::class => $this->abilities(),
            App\MoonShine\Resources\ActivityLogResource::class => $this->abilities(),
        ];

        DB::table('moonshine_user_permissions')->updateOrInsert(
            ['moonshine_user_id' => $adminId],
            [
                'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => $timestamp,
                'created_at' => $timestamp,
            ]
        );
    }

    public function down(): void
    {
        $adminId = DB::table('moonshine_users')
            ->where('email', 'admin@itcapital.top')
            ->value('id');

        if ($adminId !== null) {
            DB::table('moonshine_user_permissions')
                ->where('moonshine_user_id', $adminId)
                ->delete();
        }

        DB::table('moonshine_user_roles')
            ->where('name', 'News editor')
            ->delete();
    }

    /**
     * @return array<string, bool>
     */
    private function abilities(): array
    {
        return [
            'viewAny' => true,
            'view' => true,
            'create' => true,
            'update' => true,
            'delete' => true,
            'massDelete' => true,
            'restore' => true,
            'forceDelete' => true,
        ];
    }
};
