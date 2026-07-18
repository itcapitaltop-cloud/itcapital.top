<?php

use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSummary;
use App\MoonShine\Resources\UserResource;
use App\Services\User\UserSummaryService;

/**
 * user_summary.in_out_saldo — сальдо IN/OUT для таблицы пользователей в админке:
 * принятые депозиты минус принятые выводы, отклонённые и ожидающие не учитываются.
 *
 * @see \App\Services\User\UserSummaryService::inOutSaldo
 */
it('считает in_out_saldo как принятые депозиты минус принятые выводы', function () {
    $user = User::factory()->create();

    // Принятый депозит — учитывается в IN.
    Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 100,
        'accepted_at' => now(),
    ]);

    // Отклонённый депозит — не учитывается.
    Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 50,
        'accepted_at' => null,
        'rejected_at' => now(),
    ]);

    // Принятый вывод — учитывается в OUT.
    Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::WITHDRAW,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 30,
        'accepted_at' => now(),
    ]);

    // Вывод в ожидании — не учитывается, пока не принят.
    Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::WITHDRAW,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 20,
        'accepted_at' => null,
    ]);

    $saldo = app(UserSummaryService::class)->computeFor($user->id)['in_out_saldo'];

    // 100 (принятый депозит) − 30 (принятый вывод) = 70.
    expect((float) $saldo)->toEqual(70.00);

    // Проекция user_summary синхронизируется обсервером при создании транзакций.
    expect((float) UserSummary::query()->find($user->id)->in_out_saldo)->toEqual(70.00);
});

it('позволяет сортировать пользователей по in_out_saldo', function () {
    $richUser = User::factory()->create();
    $poorUser = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $richUser->id,
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 500,
        'accepted_at' => now(),
    ]);

    Transaction::factory()->create([
        'user_id' => $poorUser->id,
        'trx_type' => TrxTypeEnum::WITHDRAW,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 200,
        'accepted_at' => now(),
    ]);

    $sorted = (new UserResource())->query()
        ->orderByDesc('in_out_saldo')
        ->pluck('users.id');

    expect($sorted->search($richUser->id))->toBeLessThan($sorted->search($poorUser->id));
});
