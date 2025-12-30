<?php

namespace App\Enums;

enum LogActionTypeEnum: string
{
    case UPDATE_ITC_PACKAGE_AMOUNT           = 'update_itc_package_amount';
    case UPDATE_ITC_PACKAGE_TYPE             = 'update_itc_package_type';
    case UPDATE_ITC_PACKAGE_PROFIT_PERCENT   = 'update_itc_package_profit_percent';
    case UPDATE_ITC_PACKAGE_CREATED_AT       = 'update_itc_package_created_at';
    case UPDATE_REFERRER                     = 'update_referrer';
    case UPDATE_USER_RANK                    = 'update_user_rank';
    case UPDATE_USER_EMAIL                   = 'update_user_email';
    case UPDATE_USER_USERNAME                = 'update_user_username';
    case UPDATE_INVESTMENTS_SUM              = 'update_investments_sum';
    case UPDATE_PARTNER_BALANCE              = 'update_partner_balance';
    case UPDATE_WITHDRAW_AMOUNT = 'update_withdraw_amount';
    case DELETE_PACKAGE_REINVEST_PROFIT      = 'delete_package_reinvest_profit';
    case APPROVE_TRANSACTION                 = 'approve_transaction';
    case REJECT_TRANSACTION                  = 'reject_transaction';
    case MODERATE_TRANSACTION                = 'moderate_transaction';
    case WITHDRAW_PACKAGE_REINVEST_PROFIT    = 'withdraw_package_reinvest_profit';
    case EXTEND_PACKAGE_REINVEST_PROFIT      = 'extend_package_reinvest_profit';
    case CLOSE_ITC_PACKAGE = 'close_itc_package';

    public function label(): string
    {
        return match($this) {
            self::UPDATE_ITC_PACKAGE_AMOUNT         => 'Изменение суммы пакета',
            self::UPDATE_ITC_PACKAGE_TYPE           => 'Изменение типа пакета',
            self::UPDATE_ITC_PACKAGE_PROFIT_PERCENT => 'Изменение доходности пакета',
            self::UPDATE_ITC_PACKAGE_CREATED_AT     => 'Изменение даты открытия пакета',
            self::UPDATE_REFERRER                   => 'Изменение реферера',
            self::UPDATE_USER_RANK                  => 'Изменение ранга пользователя',
            self::UPDATE_USER_EMAIL                 => 'Изменение email пользователя',
            self::UPDATE_USER_USERNAME              => 'Изменение имени пользователя',
            self::UPDATE_INVESTMENTS_SUM            => 'Изменение суммы инвестиций',
            self::UPDATE_PARTNER_BALANCE            => 'Изменение партнёрского баланса',
            self::UPDATE_WITHDRAW_AMOUNT            => 'Изменение суммы вывода',
            self::DELETE_PACKAGE_REINVEST_PROFIT    => 'Удаление реинвеста профита пакета',
            self::APPROVE_TRANSACTION               => 'Одобрение транзакции',
            self::REJECT_TRANSACTION                => 'Отклонение транзакции',
            self::MODERATE_TRANSACTION                => 'Изменение статуса транзакции на "На модерации"',
            self::WITHDRAW_PACKAGE_REINVEST_PROFIT  => 'Вывод реинвеста профита на баланс',
            self::EXTEND_PACKAGE_REINVEST_PROFIT    => 'Продление срока реинвеста профита',
            self::CLOSE_ITC_PACKAGE    => 'Закрытие пакета',
        };
    }
}
