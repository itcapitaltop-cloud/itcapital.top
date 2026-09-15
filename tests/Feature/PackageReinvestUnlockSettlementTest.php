<?php

use App\Contracts\Packages\PackageReinvestRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Itc\Packages;
use App\Models\ItcPackage;
use App\Models\PackageProfit;
use App\Models\PackageProfitReinvest;
use App\Models\PackageProfitReinvestWithdraw;
use App\Models\ReinvestToPackageBody;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\ItcPackageRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * @return array{0: User, 1: ItcPackage}
 */
function createReinvestSettlementPackage(bool $expired = false): array
{
    $user = User::factory()->create();

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
        'work_to' => $expired ? now()->subDay() : now()->addMonths(6),
    ]);

    return [$user, $package];
}

/**
 * created_at не входит в $fillable ни у PackageProfit, ни у PackageProfitReinvest,
 * поэтому дату создания приходится проставлять принудительно: лента событий в
 * continuePackageWork() упорядочена именно по ней.
 */
function backdateSettlementRow(Model $model, CarbonInterface $createdAt): void
{
    $model->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
}

function createSettlementProfit(string $packageUuid, string $amount, CarbonInterface $createdAt): PackageProfit
{
    $profit = PackageProfit::query()->create([
        'uuid' => 'PP-' . Str::random(10),
        'package_uuid' => $packageUuid,
        'amount' => $amount,
    ]);

    backdateSettlementRow($profit, $createdAt);

    return $profit;
}

function reinvestWithdrawTransactions(int $userId)
{
    return Transaction::query()
        ->where('user_id', $userId)
        ->where('trx_type', TrxTypeEnum::WITHDRAW_PACKAGE_REINVEST_PROFIT)
        ->get();
}

/**
 * Регрессия: продление пакета сворачивало в тело все невыплаченные реинвесты, включая
 * разлоченные. Пользователю при этом обещана отложенная выплата, а деньги уже вернулись
 * в тело — то есть выплата исчезала, а сумма доставалась пакету.
 */
it('при продлении пакета сворачивает в тело только активные реинвесты', function () {
    [$user, $package] = createReinvestSettlementPackage(expired: true);
    $this->actingAs($user);

    // Лента событий восстанавливается по дивидендам, поэтому каждому реинвесту нужен
    // ровно совпадающий по сумме профит, созданный раньше него.
    createSettlementProfit($package->uuid, '100', now()->subDays(10));

    $unlocked = PackageProfitReinvest::factory()->create([
        'package_uuid' => $package->uuid,
        'amount' => '100',
        'matured_at' => now()->subDays(20),
        'unlocked_at' => now()->subDays(5),
        'payout_at' => now()->subMinute(),
    ]);
    backdateSettlementRow($unlocked, now()->subDays(9));

    createSettlementProfit($package->uuid, '50', now()->subDays(8));

    $active = PackageProfitReinvest::factory()->create([
        'package_uuid' => $package->uuid,
        'amount' => '50',
        'matured_at' => now()->subDays(20),
    ]);
    backdateSettlementRow($active, now()->subDays(7));

    Livewire::test(Packages::class)->call('continuePackageWork', $package->uuid);

    // Активный реинвест свёрнут в тело и удалён, разлоченный остался нетронутым.
    expect(PackageProfitReinvest::query()->whereKey($active->id)->exists())->toBeFalse()
        ->and(PackageProfitReinvest::query()->whereKey($unlocked->id)->exists())->toBeTrue()
        ->and($unlocked->fresh()->unlocked_at)->not->toBeNull();

    expect((float) ReinvestToPackageBody::query()->where('package_uuid', $package->uuid)->sum('amount'))
        ->toBe(50.0);

    // И обещанная выплата действительно доходит до баланса.
    $this->artisan('packages:payout-unlocked-reinvests')->assertSuccessful();

    $transactions = reinvestWithdrawTransactions($user->id);

    expect($transactions)->toHaveCount(1)
        ->and((float) $transactions->first()->amount)->toBe(100.0)
        ->and(PackageProfitReinvestWithdraw::query()->sole()->reinvest_uuid)->toBe($unlocked->uuid);
});

/**
 * Закрытие пакета выплачивает разлоченный реинвест досрочно — это верно: пакет
 * закрывается, деньги принадлежат пользователю. Важно лишь, чтобы плановая выплата
 * не заплатила за него второй раз.
 */
it('при закрытии пакета выплачивает ожидающий реинвест ровно один раз', function () {
    [$user, $package] = createReinvestSettlementPackage();

    $unlocked = PackageProfitReinvest::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '200',
    ]);

    app(ItcPackageRepository::class)->closePackage(
        $package->uuid,
        app(TransactionRepositoryContract::class),
        app(PackageReinvestRepositoryContract::class),
    );

    expect(reinvestWithdrawTransactions($user->id))->toHaveCount(1)
        ->and(PackageProfitReinvestWithdraw::query()->sole()->reinvest_uuid)->toBe($unlocked->uuid);

    // Плановая выплата после закрытия не должна платить второй раз: строка
    // package_profit_reinvest_withdraws убрала реинвест из очереди duePayout().
    $this->artisan('packages:payout-unlocked-reinvests')->assertSuccessful();

    expect(reinvestWithdrawTransactions($user->id))->toHaveCount(1)
        ->and(PackageProfitReinvestWithdraw::query()->count())->toBe(1);
});
