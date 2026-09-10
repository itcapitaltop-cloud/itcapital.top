<?php

use App\Dto\Finance\FinanceRequestFilterData;
use App\Enums\Transactions\CurrencyEnum;
use App\Enums\Transactions\TransactionStatusEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdraw;
use App\MoonShine\Pages\User\UserDetailPage;
use App\Repositories\UserFinanceRequestRepository;
use Illuminate\Support\Str;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Fields\Date;
use MoonShine\Fields\Td;
use MoonShine\Fields\Text;

/**
 * Вкладки «Ввод» и «Вывод» карточки клиента показывают заявки одного пользователя.
 * Выборку для них строит UserFinanceRequestRepository — он же покрыт этими тестами.
 *
 * @see \App\MoonShine\Pages\User\UserDetailPage
 * @see \App\Repositories\UserFinanceRequestRepository
 */
function makeDeposit(User $user, string $amount, ?string $acceptedAt = null, ?string $rejectedAt = null, ?string $hash = null): Deposit
{
    $transaction = Transaction::factory()->create([
        'uuid' => 'DEP-' . Str::random(10),
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'amount' => $amount,
        'accepted_at' => $acceptedAt,
        'rejected_at' => $rejectedAt,
    ]);

    return Deposit::query()->create([
        'uuid' => $transaction->uuid,
        'commission' => '0',
        'currency' => CurrencyEnum::USDT_TRC_20,
        'transaction_hash' => $hash ?? Str::random(64),
        'wallet_address' => 'TXtestwalletaddressXXXXXXXXXXXXXXX',
    ]);
}

function makeWithdraw(User $user, string $amount, string $commission = '0', ?string $acceptedAt = null, ?string $rejectedAt = null): Withdraw
{
    $transaction = Transaction::factory()->create([
        'uuid' => 'WTH-' . Str::random(10),
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::WITHDRAW,
        'amount' => $amount,
        'accepted_at' => $acceptedAt,
        'rejected_at' => $rejectedAt,
    ]);

    return Withdraw::query()->create([
        'uuid' => $transaction->uuid,
        'commission' => $commission,
        'currency' => CurrencyEnum::USDT_TRC_20,
        'wallet_address' => 'TXtestwalletaddressXXXXXXXXXXXXXXX',
    ]);
}

it('показывает во вкладке «Ввод» только заявки владельца карточки', function (): void {
    $user = User::factory()->create();
    $stranger = User::factory()->create();

    $own = makeDeposit($user, '100.00');
    makeDeposit($stranger, '999.00');

    $page = app(UserFinanceRequestRepository::class)
        ->paginateDeposits($user->id, new FinanceRequestFilterData());

    expect($page->total())->toBe(1)
        ->and($page->items()[0]->uuid)->toBe($own->uuid)
        ->and((float) $page->items()[0]->transaction->amount)->toEqual(100.00);
});

it('показывает во вкладке «Вывод» только заявки владельца карточки', function (): void {
    $user = User::factory()->create();
    $stranger = User::factory()->create();

    $own = makeWithdraw($user, '500.00', '15.00');
    makeWithdraw($stranger, '777.00');

    $page = app(UserFinanceRequestRepository::class)
        ->paginateWithdraws($user->id, new FinanceRequestFilterData());

    expect($page->total())->toBe(1)
        ->and($page->items()[0]->uuid)->toBe($own->uuid);

    // Колонка «К выводу» = сумма − комиссия, считается через BigDecimal без float.
    $withdraw = $page->items()[0];
    $net = \Brick\Math\BigDecimal::of($withdraw->transaction->amount)
        ->minus($withdraw->commission)
        ->toScale(2);

    expect((string) $net)->toBe('485.00');
});

it('фильтрует заявки на ввод по статусу', function (): void {
    $user = User::factory()->create();

    $moderated = makeDeposit($user, '10.00');
    $accepted = makeDeposit($user, '20.00', acceptedAt: now()->toDateTimeString());
    $rejected = makeDeposit($user, '30.00', rejectedAt: now()->toDateTimeString());

    $repository = app(UserFinanceRequestRepository::class);

    $onModeration = $repository->paginateDeposits($user->id, new FinanceRequestFilterData(status: TransactionStatusEnum::MODERATE));
    $done = $repository->paginateDeposits($user->id, new FinanceRequestFilterData(status: TransactionStatusEnum::ACCEPTED));
    $declined = $repository->paginateDeposits($user->id, new FinanceRequestFilterData(status: TransactionStatusEnum::REJECTED));

    expect($onModeration->pluck('uuid')->all())->toBe([$moderated->uuid])
        ->and($done->pluck('uuid')->all())->toBe([$accepted->uuid])
        ->and($declined->pluck('uuid')->all())->toBe([$rejected->uuid]);
});

it('фильтрует заявки на вывод по периоду', function (): void {
    $user = User::factory()->create();

    $old = makeWithdraw($user, '10.00');
    $old->forceFill(['created_at' => now()->subDays(10)])->save();

    $fresh = makeWithdraw($user, '20.00');
    $fresh->forceFill(['created_at' => now()->subDay()])->save();

    $page = app(UserFinanceRequestRepository::class)->paginateWithdraws(
        $user->id,
        new FinanceRequestFilterData(dateFrom: now()->subDays(3)->startOfDay())
    );

    expect($page->pluck('uuid')->all())->toBe([$fresh->uuid]);
});

it('разводит пагинацию вкладок разными GET-параметрами страницы', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 3) as $i) {
        makeDeposit($user, (string) ($i * 10));
    }

    $page = app(UserFinanceRequestRepository::class)
        ->paginateDeposits($user->id, new FinanceRequestFilterData(), perPage: 2);

    expect($page->total())->toBe(3)
        ->and($page->count())->toBe(2)
        ->and($page->url(2))->toContain(UserFinanceRequestRepository::DEPOSITS_PAGE_NAME . '=2')
        ->and(UserFinanceRequestRepository::WITHDRAWS_PAGE_NAME)->not->toBe(UserFinanceRequestRepository::DEPOSITS_PAGE_NAME);
});

it('отдаёт вторую страницу заявок на ввод', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 3) as $i) {
        makeDeposit($user, (string) ($i * 10));
    }

    request()->replace([UserFinanceRequestRepository::DEPOSITS_PAGE_NAME => 2]);

    $page = app(UserFinanceRequestRepository::class)
        ->paginateDeposits($user->id, new FinanceRequestFilterData(), perPage: 2);

    expect($page->currentPage())->toBe(2)
        ->and($page->count())->toBe(1);
});

it('находит заявки забаненного клиента вместе со связью user', function (): void {
    $user = User::factory()->create(['banned_at' => now()]);

    makeDeposit($user, '42.00');

    $page = app(UserFinanceRequestRepository::class)
        ->paginateDeposits($user->id, new FinanceRequestFilterData());

    // Глобальный скоуп notBanned не должен обнулять связь: карточка открывается и для забаненных.
    expect($page->total())->toBe(1)
        ->and($page->items()[0]->transaction->user)->not->toBeNull()
        ->and($page->items()[0]->transaction->user->id)->toBe($user->id);
});

it('ставит кнопки колонки «Статус» в одну строку', function (): void {
    $user = User::factory()->create();
    $deposit = makeDeposit($user, '100.00');

    // Колонка «Статус» держит несколько кнопок подряд; без flex они встают друг под друга.
    // Номер ячейки не задаётся числом, поэтому проверяем, что он найден по позиции поля Td.
    $financeTable = new ReflectionMethod(new UserDetailPage(), 'financeTable');
    $financeTable->setAccessible(true);

    $table = $financeTable->invoke(new UserDetailPage(), [
        Date::make('Дата', 'created_at'),
        Text::make('Хеш', 'transaction_hash'),
        Td::make('Статус')->fields(static fn (Td $field): array => [
            ActionButton::make('', route('admin.finance.accept', ['uuid' => $field->getData()->uuid]))
                ->icon('heroicons.check')
                ->success()
                ->async(method: 'POST'),
            ActionButton::make('', route('admin.finance.reject', ['uuid' => $field->getData()->uuid]))
                ->icon('heroicons.x-mark')
                ->error()
                ->async(method: 'POST'),
        ]),
    ], [$deposit->load('transaction')], Deposit::class);

    $html = (string) $table->render();

    preg_match_all('/<td[^>]*>/', $html, $matches);
    $statusCells = array_values(array_filter(
        $matches[0],
        static fn (string $td): bool => str_contains($td, 'data-column-selection="status"')
    ));

    expect($statusCells)->not->toBeEmpty()
        ->and($statusCells[0])->toContain('flex items-center gap-2')
        ->and($html)->toContain('finance/accept')
        ->toContain('finance/reject');

    // Колонки до «Статуса» flex получать не должны.
    $otherCells = array_filter(
        $matches[0],
        static fn (string $td): bool => ! str_contains($td, 'data-column-selection="status"')
    );

    foreach ($otherCells as $td) {
        expect($td)->not->toContain('flex items-center gap-2');
    }
});
