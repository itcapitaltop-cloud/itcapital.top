<?php

use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Transactions\CurrencyEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Http\Controllers\AdminController;
use App\Models\BusinessActivity;
use App\Models\Deposit;
use App\Models\PaymentSource;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdraw;
use App\Services\Admin\FinanceRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use MoonShine\Http\Middleware\Authenticate;
use MoonShine\Permissions\Models\MoonshineUser;

/**
 * Кнопки статуса во вкладках «Ввод»/«Вывод» карточки клиента бьют в роуты
 * admin.finance.*, а не в методы MoonShine-ресурса: внутри карточки текущий ресурс —
 * UserResource, у которого accept/reject/toModerate нет.
 *
 * Контроллер вызывается напрямую (как в AdminBeneficiaryTest и AdminUserCardExportTest):
 * тестовое окружение проекта поднимается с APP_ENV=local, поэтому web-группа требует
 * CSRF-токен и полноценный HTTP-запрос здесь не проходит.
 *
 * @see \App\Http\Controllers\AdminController::financeAccept()
 * @see \App\Traits\Moonshine\CanStatusModifyTrait
 */
function adminActingAsMoonshine(): MoonshineUser
{
    $admin = MoonshineUser::query()->create([
        'name' => 'Админ',
        'email' => 'admin-' . Str::random(6) . '@example.test',
        'password' => bcrypt('secret'),
        'moonshine_user_role_id' => 1,
    ]);

    test()->actingAs($admin, 'moonshine');

    return $admin;
}

/**
 * Методы трейта читают uuid через request('uuid'), поэтому запрос кладётся в контейнер,
 * а не только передаётся аргументом.
 */
function financeRequestFor(string $uuid, array $extra = []): Request
{
    // accept()/reject()/toModerate() редиректят на referer, а MoonShineJsonResponse::redirect()
    // типизирован строкой — без заголовка вызов упал бы на null.
    $request = Request::create(
        '/itcapitalmoonshineadminpanel/finance',
        'POST',
        ['uuid' => $uuid] + $extra,
        server: ['HTTP_REFERER' => '/itcapitalmoonshineadminpanel/users?tab=deposits']
    );
    app()->instance('request', $request);

    return $request;
}

function financeDeposit(User $user, string $amount = '100.00', ?string $acceptedAt = null, ?string $rejectedAt = null): Deposit
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
        'transaction_hash' => Str::random(64),
        'wallet_address' => 'TXtestwalletaddressXXXXXXXXXXXXXXX',
    ]);
}

function financeWithdraw(User $user, string $amount = '50.00', ?string $acceptedAt = null, ?string $rejectedAt = null): Withdraw
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
        'commission' => '0',
        'currency' => CurrencyEnum::USDT_TRC_20,
        'wallet_address' => 'TXtestwalletaddressXXXXXXXXXXXXXXX',
    ]);
}

beforeEach(function (): void {
    Notification::fake();
});

it('одобряет заявку на ввод из карточки клиента и пишет бизнес-событие', function (): void {
    adminActingAsMoonshine();
    $user = User::factory()->create();
    $deposit = financeDeposit($user, '250.00', rejectedAt: now()->toDateTimeString());

    expect($deposit->transaction->accepted_at)->toBeNull()
        ->and($deposit->transaction->rejected_at)->not->toBeNull();

    (new AdminController())->financeAccept(financeRequestFor($deposit->uuid));

    $transaction = Transaction::query()->where('uuid', $deposit->uuid)->sole();

    expect($transaction->accepted_at)->not->toBeNull()
        ->and($transaction->rejected_at)->toBeNull();

    $activity = BusinessActivity::query()
        ->where('description', ActivityEventTypeEnum::DepositApproved->value)
        ->sole();

    expect($activity->user_id)->toBe($user->id)
        ->and($activity->getExtraProperty('feeds'))
        ->toContain(ActivityFeedTypeEnum::Finance->value)
        ->toContain(ActivityFeedTypeEnum::UserDetailUser->value);
});

it('отклоняет заявку на вывод из карточки клиента', function (): void {
    adminActingAsMoonshine();
    $user = User::factory()->create();
    $withdraw = financeWithdraw($user, '80.00');

    (new AdminController())->financeReject(financeRequestFor($withdraw->uuid));

    $transaction = Transaction::query()->where('uuid', $withdraw->uuid)->sole();

    expect($transaction->rejected_at)->not->toBeNull()
        ->and($transaction->accepted_at)->toBeNull()
        ->and(BusinessActivity::query()
            ->where('description', ActivityEventTypeEnum::WithdrawRejected->value)
            ->exists())->toBeTrue();
});

it('возвращает заявку на модерацию из карточки клиента', function (): void {
    adminActingAsMoonshine();
    $user = User::factory()->create();
    $deposit = financeDeposit($user, '15.00', acceptedAt: now()->toDateTimeString());

    (new AdminController())->financeModerate(financeRequestFor($deposit->uuid));

    $transaction = Transaction::query()->where('uuid', $deposit->uuid)->sole();

    expect($transaction->accepted_at)->toBeNull()
        ->and($transaction->rejected_at)->toBeNull();
});

it('отвергает несуществующий uuid валидацией, а не фаталом', function (string $method): void {
    adminActingAsMoonshine();

    // Методы трейта ищут транзакцию через firstWhere() без проверки на null — без
    // валидации несуществующий uuid уронил бы запрос на обращении к свойству null.
    $call = fn () => (new AdminController())->{$method}(financeRequestFor('DEP-does-not-exist'));

    expect($call)->toThrow(ValidationException::class);
})->with([
    'accept' => 'financeAccept',
    'reject' => 'financeReject',
    'moderate' => 'financeModerate',
]);

it('закрывает роуты смены статуса админской мидлварью MoonShine', function (string $routeName): void {
    $route = Route::getRoutes()->getByName($routeName);

    expect($route)->not->toBeNull()
        ->and($route->methods())->toContain('POST')
        ->and($route->gatherMiddleware())->toContain(Authenticate::class);
})->with([
    'accept' => 'admin.finance.accept',
    'reject' => 'admin.finance.reject',
    'moderate' => 'admin.finance.moderate',
]);

it('создаёт заявку на вывод из карточки клиента именно этому пользователю', function (): void {
    adminActingAsMoonshine();
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    // Справочник способов выплаты живёт в БД и обнуляется RefreshDatabase.
    PaymentSource::query()->create(['source' => 'crypto']);

    // Модалка вкладки «Вывод» подставляет user_id владельца карточки скрытым полем.
    $request = Request::create('/itcapitalmoonshineadminpanel/withdraw/create', 'POST', [
        'user_id' => $user->id,
        'amount' => '150',
        'source' => 'crypto',
        'wallet_address' => 'TXtestwalletaddressXXXXXXXXXXXXXXX',
    ]);
    app()->instance('request', $request);

    (new AdminController())->withdrawCreate($request, app(FinanceRequestService::class));

    $transaction = Transaction::query()
        ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
        ->sole();

    expect($transaction->user_id)->toBe($user->id)
        ->and($transaction->user_id)->not->toBe($stranger->id)
        ->and(Withdraw::query()->where('uuid', $transaction->uuid)->exists())->toBeTrue();
});
