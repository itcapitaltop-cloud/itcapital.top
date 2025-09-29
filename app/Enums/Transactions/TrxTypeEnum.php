<?php

namespace App\Enums\Transactions;

enum TrxTypeEnum: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAW = 'withdraw';
    case BUY_PACKAGE = 'buy_package';
    case WITHDRAW_PACKAGE_PROFIT = 'withdraw_package_profit';
    case WITHDRAW_PACKAGE = 'withdraw_package';
    case WITHDRAW_PACKAGE_REINVEST_PROFIT = 'withdraw_package_reinvest_profit';

    case HIDDEN_DEPOSIT = 'hidden_deposit';

    case PRESENT_PACKAGE = 'present_package';
    case ZERO_PRESENT_PACKAGE = 'zero_present_package';

    case PARTNER_TO_MAIN_SELF = 'partner_to_main_self';

    case PARTNER_TO_MAIN_SELF_MIRROR = 'partner_to_main_self_mirror';

    case PARTNER_TRANSFER_IN = 'partner_transfer_in';

    case PARTNER_TRANSFER_OUT = 'partner_transfer_out';
    case START_BONUS_ACCRUAL              = 'start_bonus_accrual';
    case REGULAR_PREMIUM_ACCRUAL          = 'regular_premium_accrual';
    case REGULAR_PREMIUM_TO_PARTNER       = 'regular_premium_to_partner';
    case PARTNER_BONUS_ROLLBACK           = 'partner_bonus_rollback';
    case PARTNER_TO_PACKAGE           = 'partner_to_package';

    case RANK_BONUS_ACCRUAL           = 'rank_bonus_accrual';

    case REGULAR_PREMIUM_TO_PARTNER_MIRROR = 'regular_premium_to_partner_mirror';
    case WITHDRAW_PACKAGE_TO_BALANCE = 'withdraw_package_to_balance';

    public function getName(): string
    {
        return match ($this) {
            self::DEPOSIT => 'Пополнение',
            self::WITHDRAW => 'Снятие',
            self::BUY_PACKAGE => 'Покупка пакета',
            self::WITHDRAW_PACKAGE_PROFIT => 'Вывод дивидендов',
            self::WITHDRAW_PACKAGE => 'Закрытие акции',
            self::HIDDEN_DEPOSIT => 'hidden',
            self::WITHDRAW_PACKAGE_REINVEST_PROFIT => 'Вывод реинвеста пакета на баланс',
            self::PRESENT_PACKAGE => 'Подарочный пакет',
            self::ZERO_PRESENT_PACKAGE => 'Обнуление подарочного пакета по истечении срока действия',
            self::PARTNER_TO_MAIN_SELF        => 'Перевод с партнёрского на основной',
            self::PARTNER_TO_MAIN_SELF_MIRROR => 'Списание с партнёрского (сам перевод)',
            self::PARTNER_TRANSFER_IN         => 'Получено от партнёра',
            self::PARTNER_TRANSFER_OUT        => 'Переведено партнёру',

            self::START_BONUS_ACCRUAL               => 'Стартовая премия',
            self::REGULAR_PREMIUM_ACCRUAL           => 'Регулярная премия',
            self::REGULAR_PREMIUM_TO_PARTNER        => 'Перевод регулярной премии в партнёрский',
            self::REGULAR_PREMIUM_TO_PARTNER_MIRROR => 'Списание c баланса регулярной премии',
            self::PARTNER_BONUS_ROLLBACK            => 'Сторно партнёрской премии',
            self::PARTNER_TO_PACKAGE            => 'Перевод партнерского баланса в пакет',
            self::WITHDRAW_PACKAGE_TO_BALANCE => 'Вывод части пакета на основной баланс',
            self::RANK_BONUS_ACCRUAL => 'Начисление бонуса при достижении ранга',
        };
    }

    public static function getDebits(): array
    {
        return [
            self::DEPOSIT,
            self::WITHDRAW_PACKAGE_PROFIT,
            self::WITHDRAW_PACKAGE,
            self::HIDDEN_DEPOSIT,
            self::WITHDRAW_PACKAGE_REINVEST_PROFIT,
            self::PARTNER_TO_MAIN_SELF,
            self::PARTNER_TRANSFER_IN,
            self::START_BONUS_ACCRUAL,
            self::REGULAR_PREMIUM_ACCRUAL,
            self::REGULAR_PREMIUM_TO_PARTNER,
            self::WITHDRAW_PACKAGE_TO_BALANCE,
            self::RANK_BONUS_ACCRUAL,

        ];
    }

    public static function getCredits(): array
    {
        return [
            self::BUY_PACKAGE, self::WITHDRAW,
            self::PARTNER_TO_MAIN_SELF_MIRROR,
            self::PARTNER_TRANSFER_OUT,
            self::REGULAR_PREMIUM_TO_PARTNER_MIRROR,
            self::PARTNER_BONUS_ROLLBACK,
            self::PARTNER_TO_PACKAGE,
        ];
    }
}
