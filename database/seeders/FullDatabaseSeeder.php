<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class FullDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Создаём 10 пользователей с реалистичной датой регистрации
        foreach (range(1, 10) as $_) {
            // Пользователь зарегистрирован от 6 до 1 месяца назад
            $userCreatedAt = $faker->dateTimeBetween('-6 months', '-1 month');

            $userId = DB::table('users')->insertGetId([
                'first_name' => $faker->firstName,
                'last_name'  => $faker->lastName,
                'username'   => $faker->userName,
                'email'      => $faker->unique()->safeEmail,
                'password'   => bcrypt('secret'),
                'banned_at'  => null,
                'created_at' => $userCreatedAt,
                'updated_at' => $userCreatedAt,
            ]);

            // Партнёрская связь (иногда)
            if (DB::table('users')->count() > 1 && rand(0,1)) {
                $partnerId = DB::table('users')
                    ->where('id', '<>', $userId)
                    ->inRandomOrder()
                    ->value('id');

                DB::table('partners')->insert([
                    'user_id'    => $userId,
                    'partner_id' => $partnerId,
                    'created_at' => $faker->dateTimeBetween($userCreatedAt, 'now'),
                    'updated_at' => now(),
                ]);
            }

            // 1) Депозиты + транзакции + подарки
            foreach (range(1, rand(1, 2)) as $_d) {
                $depUuid     = (string) Str::uuid();
                // Депозит не может быть до регистрации пользователя
                $depCreated  = $faker->dateTimeBetween($userCreatedAt, 'now');
                $depAccepted = (clone $depCreated)->modify('+'.rand(1,72).' hours');

                DB::table('deposits')->insert([
                    'uuid'             => $depUuid,
                    'commission'       => $faker->randomFloat(2, 0, 5),
                    'currency'         => 'usdt_trc_20',
                    'transaction_hash' => $faker->sha256,
                    'wallet_address'   => $faker->regexify('0x[0-9a-f]{40}'),
                    'created_at'       => $depCreated,
                    'updated_at'       => $depCreated,
                ]);

                DB::table('transactions')->insert([
                    'uuid'         => $depUuid,
                    'user_id'      => $userId,
                    'amount'       => $faker->randomFloat(2, 50, 500),
                    'balance_type' => 'main',
                    'trx_type'     => 'deposit',
                    'accepted_at'  => $depAccepted,
                    'created_at'   => $depCreated,
                    'updated_at'   => $depAccepted,
                ]);

                if (rand(0,1)) {
                    $giftUuid = (string) Str::uuid();
                    $giftAt   = (clone $depAccepted)->modify('+'.rand(1,24).' hours');

                    DB::table('deposit_gifts')->insert([
                        'uuid'       => $giftUuid,
                        'comment'    => $faker->sentence,
                        'created_at' => $giftAt,
                        'updated_at' => $giftAt,
                    ]);
                }
            }

            // 2) Выводы со счёта + транзакции
            foreach (range(1, rand(0,1)) as $_w) {
                $wdUuid       = (string) Str::uuid();
                $wdCreated    = $faker->dateTimeBetween($userCreatedAt, 'now');
                $wdAccepted   = (clone $wdCreated)->modify('+'.rand(1,48).' hours');

                DB::table('withdraws')->insert([
                    'uuid'           => $wdUuid,
                    'trx_hash'       => $faker->sha256,
                    'commission'     => $faker->randomFloat(2, 0, 5),
                    'wallet_address' => $faker->regexify('0x[0-9a-f]{40}'),
                    'currency'       => 'usdt_trc_20',
                    'created_at'     => $wdCreated,
                    'updated_at'     => $wdCreated,
                ]);

                DB::table('transactions')->insert([
                    'uuid'         => $wdUuid,
                    'user_id'      => $userId,
                    'amount'       => $faker->randomFloat(2, 10, 200),
                    'balance_type' => 'main',
                    'trx_type'     => 'withdraw',
                    'accepted_at'  => $wdAccepted,
                    'created_at'   => $wdCreated,
                    'updated_at'   => $wdAccepted,
                ]);
            }

            // 3) ITC-пакеты + транзакции + дивиденды + реинвесты + выводы + закрытия
            foreach (range(1, rand(1, 3)) as $_pkg) {
                $itcUuid      = (string) Str::uuid();
                // Покупка пакета — не раньше регистрации пользователя
                $pkgCreated   = $faker->dateTimeBetween($userCreatedAt, 'now');
                $pkgAccepted  = (clone $pkgCreated)->modify('+'.rand(1,72).' hours');
                $workTo       = (clone $pkgCreated)->modify('+'.rand(30,365).' days');

                DB::table('itc_packages')->insert([
                    'uuid'                 => $itcUuid,
                    'month_profit_percent' => $faker->randomFloat(2, 1, 10),
                    'type'                 => $faker->randomElement(['standard','privilege','vip']),
                    'work_to'              => $workTo,
                    'created_at'           => $pkgCreated,
                    'updated_at'           => $pkgCreated,
                ]);

                DB::table('transactions')->insert([
                    'uuid'         => $itcUuid,
                    'user_id'      => $userId,
                    'amount'       => $faker->randomFloat(2, 100, 1000),
                    'balance_type' => 'main',
                    'trx_type'     => 'buy_package',
                    'accepted_at'  => $pkgAccepted,
                    'created_at'   => $pkgCreated,
                    'updated_at'   => $pkgAccepted,
                ]);

                // Начисления дивидендов
                foreach (range(1, rand(1,2)) as $_pr) {
                    $ppUuid     = (string) Str::uuid();
                    $profitAt   = $faker->dateTimeBetween($pkgAccepted, 'now');

                    DB::table('package_profits')->insert([
                        'uuid'         => $ppUuid,
                        'package_uuid' => $itcUuid,
                        'amount'       => $faker->randomFloat(2, 5, 50),
                        'created_at'   => $profitAt,
                        'updated_at'   => $profitAt,
                    ]);

                    // Реинвест
                    if (rand(0,1)) {
                        $rUuid      = (string) Str::uuid();
                        $reinvestAt = (clone $profitAt)->modify('+'.rand(1,48).' hours');

                        DB::table('package_profit_reinvests')->insert([
                            'uuid'         => $rUuid,
                            'package_uuid' => $itcUuid,
                            'amount'       => $faker->randomFloat(2, 1, 20),
                            'created_at'   => $reinvestAt,
                            'updated_at'   => $reinvestAt,
                        ]);

                        DB::table('transactions')->insert([
                            'uuid'         => $rUuid,
                            'user_id'      => $userId,
                            'amount'       => $faker->randomFloat(2, 1, 20),
                            'balance_type' => 'main',
                            'trx_type'     => 'withdraw_package_profit',
                            'accepted_at'  => $reinvestAt,
                            'created_at'   => $reinvestAt,
                            'updated_at'   => $reinvestAt,
                        ]);
                    }

                    // Вывод дивидендов
                    if (rand(0,1)) {
                        $wppUuid    = (string) Str::uuid();
                        $withdrawAt = (clone $profitAt)->modify('+'.rand(1,48).' hours');

                        DB::table('package_profit_withdraws')->insert([
                            'uuid'         => $wppUuid,
                            'package_uuid' => $itcUuid,
                            'created_at'   => $withdrawAt,
                            'updated_at'   => $withdrawAt,
                        ]);

                        DB::table('transactions')->insert([
                            'uuid'         => $wppUuid,
                            'user_id'      => $userId,
                            'amount'       => $faker->randomFloat(2, 1, 20),
                            'balance_type' => 'main',
                            'trx_type'     => 'withdraw_package_profit',
                            'accepted_at'  => $withdrawAt,
                            'created_at'   => $withdrawAt,
                            'updated_at'   => $withdrawAt,
                        ]);
                    }
                }

                // Плановые реинвесты
                if (rand(0,1)) {
                    $prUuid   = (string) Str::uuid();
                    $expireAt = (clone $pkgCreated)->modify('+'.rand(10,100).' days');

                    DB::table('package_reinvests')->insert([
                        'uuid'         => $prUuid,
                        'package_uuid' => $itcUuid,
                        'expire'       => $expireAt,
                        'created_at'   => $pkgCreated,
                        'updated_at'   => $pkgCreated,
                    ]);
                }

                // Закрытие пакета
                if (rand(0,1)) {
                    $pwUuid  = (string) Str::uuid();
                    $closeAt = $faker->dateTimeBetween($pkgAccepted, 'now');

                    DB::table('package_withdraws')->insert([
                        'uuid'         => $pwUuid,
                        'package_uuid' => $itcUuid,
                        'created_at'   => $closeAt,
                        'updated_at'   => $closeAt,
                    ]);

                    DB::table('transactions')->insert([
                        'uuid'         => $pwUuid,
                        'user_id'      => $userId,
                        'amount'       => $faker->randomFloat(2, 50, 500),
                        'balance_type' => 'main',
                        'trx_type'     => 'withdraw_package',
                        'accepted_at'  => $closeAt,
                        'created_at'   => $closeAt,
                        'updated_at'   => $closeAt,
                    ]);
                }
            }
        }
    }
}
