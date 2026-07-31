<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Http\Controllers\AdminController;
use App\Models\ItcPackage;
use App\Models\PackageProfitReinvest;
use App\Models\Partner;
use App\Models\PartnerClosure;
use App\Models\Transaction;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

it('exports the user card with template rows and dashboard package total', function (): void {
    $referrer = User::factory()->create(['username' => 'sponsor-user']);
    $user = User::factory()->create([
        'first_name' => 'Иван',
        'last_name' => 'Петров',
        'username' => 'ivan-petrov',
        'rank' => 3,
        'telegram' => '@ivan_tg',
    ]);

    Partner::query()->create(['user_id' => $user->id, 'partner_id' => $referrer->id]);
    PartnerClosure::query()->create(['ancestor_id' => $user->id, 'descendant_id' => $user->id, 'depth' => 0]);
    PartnerClosure::query()->create(['ancestor_id' => $referrer->id, 'descendant_id' => $user->id, 'depth' => 1]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => 1000,
        'balance_type' => BalanceTypeEnum::MAIN,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);
    $package = ItcPackage::factory()->create([
        'uuid' => $transaction->uuid,
        'type' => PackageTypeEnum::STANDARD,
    ]);

    PackageProfitReinvest::query()->create([
        'uuid' => 'PPR-card-export-test',
        'package_uuid' => $package->uuid,
        'amount' => 150,
        'matured_at' => now()->addDays(180),
    ]);

    $response = (new AdminController())->exportUserCard($user->id);

    ob_start();
    $response->sendContent();
    $xlsx = (string) ob_get_clean();

    $path = tempnam(sys_get_temp_dir(), 'user-card-export-');
    file_put_contents($path, $xlsx);

    $rows = IOFactory::load($path)->getActiveSheet()->toArray();

    @unlink($path);

    expect($response->headers->get('content-type'))->toContain('spreadsheetml.sheet')
        ->and(array_slice(array_column($rows, 0), 0, 11))->toBe([
            'Фамилия Имя',
            'Никнейм',
            'Номер линии',
            'Кто пригласил',
            'Город',
            'Телефон',
            'Социальные сети',
            'Пакеты Сумма',
            'Токены',
            'Обучение',
            'Ранг',
        ])
        ->and($rows[0][1])->toBe('Иван Петров')
        ->and($rows[1][1])->toBe('ivan-petrov')
        ->and((int) $rows[2][1])->toBe(1)
        ->and($rows[3][1])->toBe('sponsor-user')
        ->and($rows[4][1])->toBeNull()
        ->and($rows[5][1])->toBeNull()
        ->and($rows[6][1])->toBe('@ivan_tg')
        ->and((float) $rows[7][1])->toBe(1150.0)
        ->and((float) $rows[8][1])->toBe(0.0)
        ->and($rows[9][1])->toBeNull()
        ->and((int) $rows[10][1])->toBe(3)
        ->and($rows[12][0])->toBe('Рефералы')
        ->and($rows[12][1])->toBe('Нет рефералов');
});

it('exports the whole referral structure as an indented tree with admin profile links', function (): void {
    $user = User::factory()->create(['username' => 'root-user']);

    $first = User::factory()->create([
        'username' => 'line1-user',
        'first_name' => 'Пётр',
        'last_name' => 'Сидоров',
    ]);
    $second = User::factory()->create([
        'username' => 'line2-user',
        'first_name' => 'Анна',
        'last_name' => 'Иванова',
    ]);
    $third = User::factory()->create([
        'username' => 'line3-user',
        'first_name' => 'Олег',
        'last_name' => 'Кузнецов',
        'banned_at' => now(),
    ]);

    $chain = [$user, $first, $second, $third];

    foreach ($chain as $depth => $descendant) {
        if ($depth > 0) {
            Partner::query()->create([
                'user_id' => $descendant->id,
                'partner_id' => $chain[$depth - 1]->id,
            ]);
        }

        for ($ancestor = 0; $ancestor <= $depth; $ancestor++) {
            PartnerClosure::query()->create([
                'ancestor_id' => $chain[$ancestor]->id,
                'descendant_id' => $descendant->id,
                'depth' => $depth - $ancestor,
            ]);
        }
    }

    $response = (new AdminController())->exportUserCard($user->id);

    ob_start();
    $response->sendContent();
    $xlsx = (string) ob_get_clean();

    $path = tempnam(sys_get_temp_dir(), 'user-card-export-');
    file_put_contents($path, $xlsx);

    $sheet = IOFactory::load($path)->getActiveSheet();
    $rows = $sheet->toArray();

    @unlink($path);

    expect($rows[12])->toBe(['Рефералы', 'Всего: 3'])
        ->and($rows[13])->toBe(['Пётр Сидоров', 'Линия 1'])
        ->and($rows[14])->toBe(['    Анна Иванова', 'Линия 2'])
        ->and($rows[15])->toBe(['        Олег Кузнецов', 'Линия 3'])
        ->and($sheet->getCell('A14')->getHyperlink()->getUrl())->toContain((string) $first->id)
        ->and($sheet->getCell('A15')->getHyperlink()->getUrl())->toContain((string) $second->id)
        ->and($sheet->getCell('A16')->getHyperlink()->getUrl())->toContain((string) $third->id);
});
