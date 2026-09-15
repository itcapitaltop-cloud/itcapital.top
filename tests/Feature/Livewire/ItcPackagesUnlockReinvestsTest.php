<?php

use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Itc\Packages;
use App\Models\BusinessActivity;
use App\Models\ItcPackage;
use App\Models\PackageProfitReinvest;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

/**
 * @return array{0: User, 1: ItcPackage}
 */
function createReinvestUnlockPackage(
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

it('снимает только созревшие реинвесты и не трогает замороженные', function () {
    [$user, $package] = createReinvestUnlockPackage();
    $this->actingAs($user);

    $matured = PackageProfitReinvest::factory()->matured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '150',
    ]);
    $frozen = PackageProfitReinvest::factory()->notMatured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '40',
    ]);

    Livewire::test(Packages::class)
        ->call('unlockMaturedReinvests', $package->uuid)
        ->assertDispatched('new-system-notification', type: 'success');

    expect($matured->fresh()->unlocked_at)->not->toBeNull()
        ->and($matured->fresh()->payout_at)->not->toBeNull()
        ->and($frozen->fresh()->unlocked_at)->toBeNull()
        ->and($frozen->fresh()->payout_at)->toBeNull();
});

it('снимает легаси-реинвест без даты разморозки', function () {
    [$user, $package] = createReinvestUnlockPackage();
    $this->actingAs($user);

    // matured_at = NULL: ветка COALESCE(matured_at, created_at) в скоупе unlockable().
    $legacy = PackageProfitReinvest::factory()->legacy()->create([
        'package_uuid' => $package->uuid,
        'amount' => '70',
    ]);

    Livewire::test(Packages::class)
        ->call('unlockMaturedReinvests', $package->uuid)
        ->assertDispatched('new-system-notification', type: 'success');

    expect($legacy->fresh()->unlocked_at)->not->toBeNull();
});

it('назначает выплату ровно через календарный месяц', function () {
    [$user, $package] = createReinvestUnlockPackage();
    $this->actingAs($user);

    PackageProfitReinvest::factory()->matured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '150',
    ]);

    Livewire::test(Packages::class)->call('unlockMaturedReinvests', $package->uuid);

    $reinvest = PackageProfitReinvest::query()->where('package_uuid', $package->uuid)->sole();

    expect($reinvest->payout_at->format('Y-m-d H:i:s'))
        ->toBe($reinvest->unlocked_at->copy()->addMonthNoOverflow()->format('Y-m-d H:i:s'));
});

it('назначает выплату без перескока через конец февраля', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31 12:00:00'));

    [$user, $package] = createReinvestUnlockPackage();
    $this->actingAs($user);

    PackageProfitReinvest::factory()->create([
        'package_uuid' => $package->uuid,
        'amount' => '150',
        'matured_at' => Carbon::parse('2026-01-01 12:00:00'),
    ]);

    Livewire::test(Packages::class)->call('unlockMaturedReinvests', $package->uuid);

    // addMonth() дал бы 3 марта; addMonthNoOverflow() прижимает к последнему дню февраля.
    expect(PackageProfitReinvest::query()->sole()->payout_at->format('Y-m-d H:i:s'))
        ->toBe('2026-02-28 12:00:00');

    Carbon::setTestNow();
});

it('повторное нажатие ничего не снимает и не двигает дату выплаты', function () {
    [$user, $package] = createReinvestUnlockPackage();
    $this->actingAs($user);

    PackageProfitReinvest::factory()->matured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '150',
    ]);

    $component = Livewire::test(Packages::class);
    $component->call('unlockMaturedReinvests', $package->uuid)
        ->assertDispatched('new-system-notification', type: 'success');

    $firstPayoutAt = PackageProfitReinvest::query()->sole()->payout_at->format('Y-m-d H:i:s');

    Carbon::setTestNow(now()->addHour());

    // Компонент перехватывает InvalidAmountException и показывает уведомление об ошибке.
    $component->call('unlockMaturedReinvests', $package->uuid)
        ->assertDispatched('new-system-notification', type: 'error');

    expect(PackageProfitReinvest::query()->sole()->payout_at->format('Y-m-d H:i:s'))
        ->toBe($firstPayoutAt);

    Carbon::setTestNow();
});

it('запрещает снятие реинвестов с чужого пакета', function () {
    [, $package] = createReinvestUnlockPackage();

    PackageProfitReinvest::factory()->matured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '150',
    ]);

    $stranger = User::factory()->create();
    $this->actingAs($stranger);

    Livewire::test(Packages::class)
        ->call('unlockMaturedReinvests', $package->uuid)
        ->assertForbidden();

    expect(PackageProfitReinvest::query()->sole()->unlocked_at)->toBeNull();
});

it('пишет бизнес-событие о снятии реинвестов с датой выплаты', function () {
    [$user, $package] = createReinvestUnlockPackage();
    $this->actingAs($user);

    PackageProfitReinvest::factory()->matured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '150',
    ]);
    PackageProfitReinvest::factory()->matured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '50',
    ]);

    Livewire::test(Packages::class)->call('unlockMaturedReinvests', $package->uuid);

    $activity = BusinessActivity::query()
        ->where('description', ActivityEventTypeEnum::PackageReinvestUnlocked->value)
        ->sole();

    expect($activity->user_id)->toBe($user->id)
        ->and((float) $activity->getExtraProperty('amount'))->toBe(200.0)
        ->and($activity->getExtraProperty('package_uuid'))->toBe($package->uuid)
        ->and((int) $activity->getExtraProperty('count'))->toBe(2)
        ->and($activity->getExtraProperty('payout_at'))->not->toBeNull();

    // ActivityManager разбирает тип события через match без ветки default: новый case
    // без своей ветки уронил бы всю ленту с UnhandledMatchError. Плейсхолдер :date
    // берётся именно из свойства payout_at.
    $text = app(App\ActivityLog\ActivityManager::class)->resolve($activity);

    expect($text)->toContain('200.00')
        ->and($text)->not->toBe(__('activity/feed.unknown_event'));
});

/**
 * Имена агрегатов в userPackagesWithFinancials() зафиксированы вьюхой, а каждое чтение
 * там прикрыто `?? 0` / `?? null`: опечатка не уронит страницу, а молча спрячет кнопку
 * и строку ожидающей выплаты. Поэтому рендер проверяется явно.
 */
it('показывает кнопку снятия, когда есть созревший реинвест', function () {
    [$user, $package] = createReinvestUnlockPackage();
    $this->actingAs($user);

    PackageProfitReinvest::factory()->matured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '150',
    ]);

    Livewire::test(Packages::class)
        ->assertSee(__('components_account_itc_package_unlock_reinvests_action', ['amount' => '150']))
        ->assertDontSee(__('components_account_itc_package_unlocked_reinvests_label'));
});

it('показывает строку ожидающей выплаты после снятия', function () {
    [$user, $package] = createReinvestUnlockPackage();
    $this->actingAs($user);

    PackageProfitReinvest::factory()->matured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '150',
    ]);

    Livewire::test(Packages::class)
        ->call('unlockMaturedReinvests', $package->uuid)
        ->assertSee(__('components_account_itc_package_unlocked_reinvests_label'))
        ->assertSee(now()->addMonthNoOverflow()->format('d.m.Y'))
        // Снимать больше нечего — кнопка исчезает.
        ->assertDontSee(__('components_account_itc_package_unlock_reinvests_action', ['amount' => '150']));
});

it('не рендерит ни кнопку, ни строку, когда снимать нечего', function () {
    [$user, $package] = createReinvestUnlockPackage();
    $this->actingAs($user);

    PackageProfitReinvest::factory()->notMatured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '150',
    ]);

    Livewire::test(Packages::class)
        ->assertDontSee(__('components_account_itc_package_unlock_reinvests_action', ['amount' => '150']))
        ->assertDontSee(__('components_account_itc_package_unlocked_reinvests_label'));
});

/**
 * Регрессия: карточка показывала «реинвестировано» по reinvest_profits_sum_amount, куда
 * разлоченный реинвест продолжал попадать. В итоге одна и та же сумма выводилась дважды —
 * и как работающий реинвест, и как ожидающая выплата, хотя в базе начисления её уже нет.
 */
it('убирает разлоченный реинвест из суммы «реинвестировано» в карточке', function () {
    [$user, $package] = createReinvestUnlockPackage();
    $this->actingAs($user);

    PackageProfitReinvest::factory()->matured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '150',
    ]);

    // Пока реинвест работает — он показан как «реинвестировано».
    Livewire::test(Packages::class)
        ->assertSee(__('components_account_itc_package_reinvested'));

    Livewire::test(Packages::class)->call('unlockMaturedReinvests', $package->uuid);

    // После снятия строка «реинвестировано» исчезает, остаётся только ожидающая выплата.
    Livewire::test(Packages::class)
        ->assertDontSee(__('components_account_itc_package_reinvested'))
        ->assertSee(__('components_account_itc_package_unlocked_reinvests_label'));
});

it('оставляет в «реинвестировано» только неснятые реинвесты', function () {
    [$user, $package] = createReinvestUnlockPackage();
    $this->actingAs($user);

    // Созревший — будет снят.
    PackageProfitReinvest::factory()->matured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '150',
    ]);
    // Замороженный — продолжает работать и остаётся в «реинвестировано».
    PackageProfitReinvest::factory()->notMatured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '40',
    ]);

    Livewire::test(Packages::class)->call('unlockMaturedReinvests', $package->uuid);

    Livewire::test(Packages::class)
        ->assertSee(__('components_account_itc_package_reinvested'))
        ->assertSee('+40')
        ->assertSee(__('components_account_itc_package_unlocked_reinvests_label'));
});
