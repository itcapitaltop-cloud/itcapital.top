<?php

use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Itc\Packages;
use App\Models\BusinessActivity;
use App\Models\ItcPackage;
use App\Models\PackageBalanceWithdraw;
use App\Models\PackageBodyUnlock;
use App\Models\PackagePartnerTransfer;
use App\Models\ReinvestToPackageBody;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * @return array{0: User, 1: ItcPackage}
 */
function createBodyUnlockPackage(
    ?User $owner = null,
    string $bodyAmount = '1000',
    PackageTypeEnum $type = PackageTypeEnum::STANDARD,
): array {
    $user = $owner ?? User::factory()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => $bodyAmount,
        'accepted_at' => now(),
    ]);

    $package = ItcPackage::factory()->create([
        'uuid' => $transaction->uuid,
        'type' => $type,
    ]);

    return [$user, $package];
}

it('разблокирует часть тела пакета и назначает выплату через календарный месяц', function () {
    [$user, $package] = createBodyUnlockPackage();
    $this->actingAs($user);

    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '300')
        ->call('unlockPackageBodyAmount', $package->uuid)
        ->assertDispatched('new-system-notification', type: 'success');

    $unlock = PackageBodyUnlock::query()->where('package_uuid', $package->uuid)->sole();

    expect((float) $unlock->amount)->toBe(300.0)
        ->and($unlock->payout_transaction_uuid)->toBeNull()
        ->and($unlock->cancelled_at)->toBeNull()
        ->and($unlock->payout_at->format('Y-m-d H:i:s'))
        ->toBe($unlock->unlocked_at->copy()->addMonthNoOverflow()->format('Y-m-d H:i:s'));
});

it('назначает выплату без перескока через конец февраля', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31 12:00:00'));

    [$user, $package] = createBodyUnlockPackage();
    $this->actingAs($user);

    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '300')
        ->call('unlockPackageBodyAmount', $package->uuid);

    // addMonth() дал бы 3 марта; addMonthNoOverflow() прижимает к последнему дню февраля.
    expect(PackageBodyUnlock::query()->sole()->payout_at->format('Y-m-d H:i:s'))
        ->toBe('2026-02-28 12:00:00');

    Carbon::setTestNow();
});

it('отклоняет сумму больше доступного тела пакета', function () {
    [$user, $package] = createBodyUnlockPackage();
    $this->actingAs($user);

    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '1000.01')
        ->call('unlockPackageBodyAmount', $package->uuid)
        ->assertHasErrors('unlockBodyAmount');

    expect(PackageBodyUnlock::query()->count())->toBe(0);
});

it('разрешает сумму, оставляющую в теле меньше 100 ITC', function () {
    [$user, $package] = createBodyUnlockPackage();
    $this->actingAs($user);

    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '950')
        ->call('unlockPackageBodyAmount', $package->uuid)
        ->assertHasNoErrors();

    expect((float) PackageBodyUnlock::query()->sole()->amount)->toBe(950.0);
});

it('разрешает разблокировать 25 ITC из тела в 100 ITC', function () {
    [$user, $package] = createBodyUnlockPackage(bodyAmount: '100');
    $this->actingAs($user);

    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '25')
        ->call('unlockPackageBodyAmount', $package->uuid)
        ->assertHasNoErrors();

    expect((float) PackageBodyUnlock::query()->sole()->amount)->toBe(25.0);

    // Доходность обнуляется не сейчас, а в момент фактической выплаты.
    expect((float) $package->fresh()->month_profit_percent)->toBe(8.2);
});

it('разрешает разблокировать тело целиком', function () {
    [$user, $package] = createBodyUnlockPackage();
    $this->actingAs($user);

    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '1000')
        ->call('unlockPackageBodyAmount', $package->uuid)
        ->assertHasNoErrors();

    expect((float) PackageBodyUnlock::query()->sole()->amount)->toBe(1000.0);
});

it('разрешает сумму, оставляющую ровно 100 ITC', function () {
    [$user, $package] = createBodyUnlockPackage();
    $this->actingAs($user);

    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '900')
        ->call('unlockPackageBodyAmount', $package->uuid)
        ->assertHasNoErrors();

    expect((float) PackageBodyUnlock::query()->sole()->amount)->toBe(900.0);
});

it('запрещает разблокировку с подарочного пакета', function () {
    [$user, $package] = createBodyUnlockPackage(type: PackageTypeEnum::PRESENT);
    $this->actingAs($user);

    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '300')
        ->call('unlockPackageBodyAmount', $package->uuid)
        ->assertDispatched('new-system-notification', type: 'error');

    expect(PackageBodyUnlock::query()->count())->toBe(0);
});

it('разрешает разблокировку с privilege и vip пакетов', function (PackageTypeEnum $type) {
    [$user, $package] = createBodyUnlockPackage(type: $type);
    $this->actingAs($user);

    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '300')
        ->call('unlockPackageBodyAmount', $package->uuid)
        ->assertHasNoErrors();

    expect(PackageBodyUnlock::query()->where('package_uuid', $package->uuid)->count())->toBe(1);
})->with([PackageTypeEnum::PRIVILEGE, PackageTypeEnum::VIP]);

it('учитывает партнёрские переводы, реинвест в тело и уже выведенные суммы', function () {
    [$user, $package] = createBodyUnlockPackage();
    $this->actingAs($user);

    // +200 партнёрским переводом
    $transferTrx = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => '200',
        'accepted_at' => now(),
    ]);
    PackagePartnerTransfer::query()->create([
        'uuid' => $transferTrx->uuid,
        'package_uuid' => $package->uuid,
    ]);

    // +100 реинвестом в тело
    ReinvestToPackageBody::query()->create([
        'uuid' => 'RTB-' . Str::random(10),
        'package_uuid' => $package->uuid,
        'amount' => '100',
    ]);

    // −300 ранее выведенной суммой
    $withdrawTrx = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::WITHDRAW_PACKAGE_TO_BALANCE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => '300',
        'accepted_at' => now(),
    ]);
    PackageBalanceWithdraw::query()->create([
        'uuid' => $withdrawTrx->uuid,
        'package_uuid' => $package->uuid,
    ]);

    // Доступно 1000 + 200 + 100 − 300 = 1000.
    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '1000.01')
        ->call('unlockPackageBodyAmount', $package->uuid)
        ->assertHasErrors('unlockBodyAmount');

    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '1000')
        ->call('unlockPackageBodyAmount', $package->uuid)
        ->assertHasNoErrors();

    expect((float) PackageBodyUnlock::query()->sole()->amount)->toBe(1000.0);
});

it('не даёт двумя разблокировками превысить тело пакета', function () {
    [$user, $package] = createBodyUnlockPackage();
    $this->actingAs($user);

    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '600')
        ->call('unlockPackageBodyAmount', $package->uuid)
        ->assertHasNoErrors();

    // Осталось 400: 450 уже нельзя, 400 можно.
    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '450')
        ->call('unlockPackageBodyAmount', $package->uuid)
        ->assertHasErrors('unlockBodyAmount');

    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '400')
        ->call('unlockPackageBodyAmount', $package->uuid)
        ->assertHasNoErrors();

    expect(PackageBodyUnlock::query()->where('package_uuid', $package->uuid)->count())->toBe(2)
        ->and((float) PackageBodyUnlock::query()->where('package_uuid', $package->uuid)->sum('amount'))
        ->toBe(1000.0);
});

it('запрещает разблокировку с чужого пакета', function () {
    [, $package] = createBodyUnlockPackage();

    $stranger = User::factory()->create();
    $this->actingAs($stranger);

    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '300')
        ->call('unlockPackageBodyAmount', $package->uuid)
        ->assertForbidden();

    expect(PackageBodyUnlock::query()->count())->toBe(0);
});

it('пишет бизнес-событие о разблокировке тела с ожидаемыми свойствами', function () {
    [$user, $package] = createBodyUnlockPackage();
    $this->actingAs($user);

    Livewire::test(Packages::class)
        ->set('unlockBodyAmount', '300')
        ->call('unlockPackageBodyAmount', $package->uuid);

    $activity = BusinessActivity::query()
        ->where('description', ActivityEventTypeEnum::PackageBodyUnlocked->value)
        ->sole();

    $unlock = PackageBodyUnlock::query()->sole();

    expect($activity->user_id)->toBe($user->id)
        ->and((float) $activity->getExtraProperty('amount'))->toBe(300.0)
        ->and($activity->getExtraProperty('package_uuid'))->toBe($package->uuid)
        ->and($activity->getExtraProperty('unlock_uuid'))->toBe($unlock->uuid)
        ->and((float) $activity->getExtraProperty('remaining_body'))->toBe(700.0);

    // ActivityManager разбирает тип события через match без ветки default: новый case
    // без своей ветки уронил бы всю ленту с UnhandledMatchError.
    $text = app(App\ActivityLog\ActivityManager::class)->resolve($activity);

    expect($text)->toContain('300.00')
        ->and($text)->toContain($package->uuid)
        ->and($text)->not->toBe(__('activity/feed.unknown_event'));
});
