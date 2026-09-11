<?php

use App\Enums\Admin\UserCardExportField;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Выгружает карточку и возвращает разобранную книгу вместе с ответом.
 *
 * @param list<string>|null $fields
 * @return array{response: StreamedResponse, spreadsheet: Spreadsheet}
 */
function exportUserCard(User $user, ?array $fields = null): array
{
    $request = Request::create('/admin/users/card/export', 'GET', [
        'fields' => $fields ?? array_column(UserCardExportField::cases(), 'value'),
    ]);

    $response = (new AdminController())->exportUserCard($request, $user->id);

    ob_start();
    $response->sendContent();
    $xlsx = (string) ob_get_clean();

    $path = tempnam(sys_get_temp_dir(), 'user-card-export-');
    file_put_contents($path, $xlsx);

    $spreadsheet = IOFactory::load($path);

    @unlink($path);

    return ['response' => $response, 'spreadsheet' => $spreadsheet];
}

/**
 * Цепочка рефералов: каждый следующий приглашён предыдущим.
 *
 * @param list<User> $chain
 */
function linkReferralChain(array $chain): void
{
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
}

it('exports the card as a transposed sheet with the owner in column C', function (): void {
    $referrer = User::factory()->create(['username' => 'sponsor-user']);
    $user = User::factory()->create([
        'first_name' => 'Иван',
        'last_name' => 'Петров',
        'username' => 'ivan-petrov',
        'rank' => 3,
        'telegram' => '@ivan_tg',
    ]);

    linkReferralChain([$referrer, $user]);

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

    ['response' => $response, 'spreadsheet' => $spreadsheet] = exportUserCard($user);

    $sheet = $spreadsheet->getSheetByName('Главная');

    expect($response->headers->get('content-type'))->toContain('spreadsheetml.sheet')
        ->and($spreadsheet->getActiveSheetIndex())->toBe(0)
        ->and(array_map(
            static fn (int $row): mixed => $sheet->getCell([1, $row])->getValue(),
            range(3, 13)
        ))->toBe([
            'Фамилия Имя',
            'Никнейм',
            'Номер линии',
            'Реферал',
            'Город',
            'Телефон',
            'Социальные сети',
            'Пакеты Сумма',
            'Токены',
            'Обучение онлайн',
            'Ранг',
        ])
        ->and($sheet->getCell('C3')->getValue())->toBe('Иван Петров')
        ->and($sheet->getCell('C4')->getValue())->toBe('ivan-petrov')
        ->and((int) $sheet->getCell('C5')->getValue())->toBe(1)
        ->and($sheet->getCell('C9')->getValue())->toBe('@ivan_tg')
        ->and((float) $sheet->getCell('C10')->getValue())->toBe(1150.0)
        ->and((float) $sheet->getCell('C11')->getValue())->toBe(0.0)
        ->and((int) $sheet->getCell('C13')->getValue())->toBe(3);
});

it('places every referral in its own column with the tree line number', function (): void {
    $user = User::factory()->create([
        'first_name' => 'Мария',
        'last_name' => 'Соколова',
        'username' => 'root-user',
    ]);

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

    linkReferralChain([$user, $first, $second, $third]);

    $sheet = exportUserCard($user)['spreadsheet']->getSheetByName('Главная');

    expect(array_map(
        static fn (string $column): mixed => $sheet->getCell($column . '3')->getValue(),
        ['C', 'D', 'E', 'F']
    ))->toBe(['Мария Соколова', 'Пётр Сидоров', 'Анна Иванова', 'Олег Кузнецов'])
        ->and($sheet->getCell('C4')->getValue())->toBe('root-user')
        ->and($sheet->getCell('D4')->getValue())->toBe('line1-user')
        ->and(array_map(
            static fn (string $column): int => (int) $sheet->getCell($column . '5')->getValue(),
            ['C', 'D', 'E', 'F']
        ))->toBe([0, 1, 2, 3]);
});

it('sums packages and tokens across the whole tree in column B', function (): void {
    $user = User::factory()->create(['username' => 'root-user']);
    $referral = User::factory()->create(['username' => 'line1-user']);

    linkReferralChain([$user, $referral]);

    $sheet = exportUserCard($user)['spreadsheet']->getSheetByName('Главная');

    expect($sheet->getCell('B10')->getValue())->toBe('=SUM(C10:D10)')
        ->and($sheet->getCell('B11')->getValue())->toBe('=SUM(C11:D11)')
        // Служебная колонка свёрнута, как в эталоне: формулы есть, на глаза не лезут.
        ->and($sheet->getColumnDimension('B')->getVisible())->toBeFalse()
        ->and($sheet->getColumnDimension('B')->getOutlineLevel())->toBe(1);
});

it('fills fields that are not stored in the system with the reference placeholders', function (): void {
    $user = User::factory()->create(['username' => 'root-user', 'telegram' => null]);

    $sheet = exportUserCard($user)['spreadsheet']->getSheetByName('Главная');

    expect($sheet->getCell('C6')->getValue())->toBe('?')
        ->and($sheet->getCell('C7')->getValue())->toBe('?')
        ->and($sheet->getCell('C8')->getValue())->toBe('?')
        ->and($sheet->getCell('C9')->getValue())->toBe('?')
        ->and($sheet->getCell('C12')->getValue())->toBe('Проходил / не проходил');
});

it('keeps numeric rows at zero instead of a placeholder', function (): void {
    $user = User::factory()->create(['username' => 'root-user', 'rank' => 0]);

    $sheet = exportUserCard($user)['spreadsheet']->getSheetByName('Главная');

    expect($sheet->getCell('C10')->getValue())->toBe(0.0)
        ->and($sheet->getCell('C11')->getValue())->toBe(0.0)
        ->and($sheet->getCell('C13')->getValue())->toBe(0);
});

it('keeps every field on its reference row when only some fields are selected', function (): void {
    $user = User::factory()->create([
        'first_name' => 'Иван',
        'last_name' => 'Петров',
        'username' => 'ivan-petrov',
        'telegram' => '@ivan_tg',
    ]);

    $sheet = exportUserCard($user, [
        UserCardExportField::FULL_NAME->value,
        UserCardExportField::SOCIAL_NETWORKS->value,
    ])['spreadsheet']->getSheetByName('Главная');

    expect($sheet->getCell('A3')->getValue())->toBe('Фамилия Имя')
        ->and($sheet->getCell('C3')->getValue())->toBe('Иван Петров')
        ->and($sheet->getCell('A9')->getValue())->toBe('Социальные сети')
        ->and($sheet->getCell('C9')->getValue())->toBe('@ivan_tg')
        ->and($sheet->getCell('A4')->getValue())->toBeNull()
        ->and($sheet->getCell('A10')->getValue())->toBeNull()
        ->and($sheet->getCell('B10')->getValue())->toBeNull();
});

it('adds the tasks sheet with headers and dropdown validation', function (): void {
    $user = User::factory()->create(['username' => 'root-user']);

    $sheet = exportUserCard($user)['spreadsheet']->getSheetByName('задачи');

    expect($sheet)->not->toBeNull()
        ->and(array_map(
            static fn (int $column): mixed => $sheet->getCell([$column, 1])->getValue(),
            range(1, 8)
        ))->toBe([
            'Дата встречи',
            'Приоритет',
            'Статус',
            'Встреча',
            'Коментарии и отчет о событии',
            'Дата следующего события',
            'Галочку ставить проверяющий',
            'Примечания',
        ])
        ->and($sheet->getCell('D2')->getDataValidation()->getFormula1())
        ->toBe('"Назначено,Выполняется,Перенесено,Выполнено"')
        ->and($sheet->getCell('B2')->getDataValidation()->getFormula1())
        ->toBe('"1 встреча,2 встреча,3 встреча"')
        ->and($sheet->getCell('C2')->getDataValidation()->getFormula1())
        ->toBe('"Встреча онлайн,Встреча офлайн,Созвон"')
        ->and($sheet->getFreezePane())->toBe('A2');
});

it('styles the main sheet like the reference workbook', function (): void {
    $user = User::factory()->create(['username' => 'root-user']);

    $sheet = exportUserCard($user)['spreadsheet']->getSheetByName('Главная');

    expect($sheet->getStyle('A3')->getFill()->getStartColor()->getARGB())->toBe('FF00FFFF')
        ->and($sheet->getStyle('C3')->getFill()->getStartColor()->getARGB())->toBe('FFFF00FF')
        ->and($sheet->getStyle('A4')->getFill()->getStartColor()->getARGB())->toBe('FFFF00FF')
        ->and($sheet->getStyle('A13')->getFill()->getStartColor()->getARGB())->toBe('FFFBBC04')
        ->and($sheet->getStyle('C12')->getFont()->getSize())->toBe(8.0)
        ->and($sheet->getStyle('A3')->getFont()->getName())->toBe('Arial')
        ->and($sheet->getFreezePane())->toBe('B1')
        ->and($sheet->getRowDimension(1)->getRowHeight())->toBe(61.5)
        ->and($sheet->getRowDimension(2)->getRowHeight())->toBe(79.5)
        ->and($sheet->getRowDimension(3)->getRowHeight())->toBe(20.25)
        ->and($sheet->getColumnDimension('A')->getWidth())->toBe(45.75);
});

it('names the downloaded file after the user full name', function (): void {
    $user = User::factory()->create([
        'first_name' => 'Светлана',
        'last_name' => 'Волкова',
        'username' => 'svetlana',
    ]);

    $disposition = exportUserCard($user)['response']->headers->get('content-disposition');

    expect($disposition)->toContain("filename*=utf-8''")
        ->and(rawurldecode((string) $disposition))->toContain('Светлана Волкова.xlsx')
        ->and($disposition)->not->toContain('user-' . $user->id);
});

it('falls back to the username when the full name is empty', function (): void {
    $user = User::factory()->create([
        'first_name' => '',
        'last_name' => '',
        'username' => 'lonely-user',
    ]);

    $disposition = (string) exportUserCard($user)['response']->headers->get('content-disposition');

    expect($disposition)->toContain('lonely-user.xlsx');
});

it('collects the whole tree without running a query per referral', function (): void {
    $chain = [User::factory()->create(['username' => 'root-user'])];

    for ($index = 0; $index < 20; $index++) {
        $chain[] = User::factory()->create(['username' => 'referral-' . $index]);
    }

    linkReferralChain($chain);

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    exportUserCard($chain[0]);

    expect($queries)->toBeLessThan(15);
});
