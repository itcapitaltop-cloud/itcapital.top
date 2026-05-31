<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Package\PackageDefinition;
use App\Models\Transaction;
use App\Models\User;
use App\MoonShine\Resources\UserResource;
use Illuminate\Support\Facades\Artisan;
use MoonShine\MoonShineRequest;

it('recalculates ranks after an admin creates a regular package', function (): void {
    Artisan::spy();

    $user = User::factory()->create();
    $definition = PackageDefinition::factory()->create([
        'type' => PackageTypeEnum::STANDARD,
        'default_profit_percent' => '8.20',
        'duration_months' => 6,
        'is_active' => true,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => '1000.00000000',
        'balance_type' => BalanceTypeEnum::MAIN,
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);

    $request = MoonShineRequest::create('/', 'POST', [
        'user_id' => $user->id,
        'package_definition_id' => $definition->id,
        'amount' => '500',
        'percent' => '8.20',
        'duration' => 6,
    ]);

    app()->instance('request', $request);

    (new UserResource())->createPackage($request);

    Artisan::shouldHaveReceived('call')
        ->once()
        ->with('user:use-rank');
});
