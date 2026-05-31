<?php

use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Http\Controllers\AdminController;
use App\Models\BusinessActivity;
use App\Models\User;
use App\Services\ActivityLog\BusinessActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

it('exports user journal logs filtered by creation dates to xlsx', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $writeLog = function (User $targetUser, string $amount, string $createdAt, string $username): void {
        app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
            type: ActivityEventTypeEnum::PartnerTransferReceived,
            userId: $targetUser->id,
            subject: $targetUser,
            feeds: [ActivityFeedTypeEnum::UserDetailUser],
            properties: [
                'amount' => $amount,
                'from_username' => $username,
            ],
            occurredAt: Carbon::parse($createdAt),
        ));
    };

    $writeLog($user, '50.00', '2026-01-01 10:00:00', 'before-user');
    $writeLog($user, '123.45', '2026-01-15 10:00:00', 'source-user');
    $writeLog($otherUser, '777.00', '2026-01-15 10:00:00', 'other-user');

    $request = Request::create('/admin/users/export', 'GET', [
        'date_from' => '2026-01-10',
        'date_to' => '2026-01-20',
    ]);

    $response = (new AdminController())->exportUserOperations($request, $user->id);

    ob_start();
    $response->sendContent();
    $xlsx = (string) ob_get_clean();

    $path = tempnam(sys_get_temp_dir(), 'user-journal-export-');
    file_put_contents($path, $xlsx);

    $rows = IOFactory::load($path)->getActiveSheet()->toArray();

    @unlink($path);

    expect(BusinessActivity::query()->count())->toBe(3)
        ->and($response->headers->get('content-type'))->toContain('spreadsheetml.sheet')
        ->and($rows[0])->toBe(['Событие', 'Сумма операции', 'Пользователь', 'Дата'])
        ->and($rows[1][0])->toContain('Получена сумма 123.45 ITC от партнера @source-user')
        ->and($rows[1][1])->toBe('123.45')
        ->and($rows[1][2])->toBe('source-user')
        ->and($rows)->toHaveCount(2);
});
