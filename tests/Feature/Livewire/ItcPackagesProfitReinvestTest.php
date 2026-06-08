<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Itc\Packages;
use App\Models\ItcPackage;
use App\Models\PackageProfit;
use App\Models\PackageProfitReinvest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

it('реинвестирует дивиденды только один раз при повторной отправке запроса', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 1000,
        'accepted_at' => now(),
    ]);

    $package = ItcPackage::factory()->create([
        'uuid' => $transaction->uuid,
        'type' => PackageTypeEnum::STANDARD,
    ]);

    PackageProfit::query()->create([
        'uuid' => 'PP-' . Str::random(10),
        'package_uuid' => $package->uuid,
        'amount' => '57.07',
    ]);

    $component = Livewire::test(Packages::class);

    // Первая отправка реинвеста — должна пройти успешно.
    $component->call('profitReinvest', $package->uuid);

    expect(PackageProfitReinvest::query()->where('package_uuid', $package->uuid)->count())->toBe(1);
    expect((float) PackageProfitReinvest::query()->where('package_uuid', $package->uuid)->sole()->amount)
        ->toEqual(57.07);

    // Повторная отправка (например, из-за повторного клика при зависшем интернете)
    // не должна создавать второй реинвест на ту же сумму дивидендов: компонент
    // перехватывает InvalidAmountException и показывает уведомление об ошибке.
    $component->call('profitReinvest', $package->uuid)
        ->assertDispatched('new-system-notification', type: 'error');

    expect(PackageProfitReinvest::query()->where('package_uuid', $package->uuid)->count())->toBe(1);
});
