<?php

namespace App\Enums;

enum LogActionTypeEnum: string
{
    case UPDATE_ITC_PACKAGE_AMOUNT = 'update_itc_package_amount';
    case UPDATE_ITC_PACKAGE_TYPE = 'update_itc_package_type';
    case UPDATE_ITC_PACKAGE_PROFIT_PERCENT = 'update_itc_package_profit_percent';
    case UPDATE_ITC_PACKAGE_CREATED_AT = 'update_itc_package_created_at';
    case UPDATE_REFERRER = 'update_referrer';
    case UPDATE_USER_RANK = 'update_user_rank';
    case UPDATE_USER_EMAIL = 'update_user_email';
    case UPDATE_USER_USERNAME = 'update_user_username';
    case UPDATE_INVESTMENTS_SUM = 'update_investments_sum';
    case UPDATE_PARTNER_BALANCE = 'update_partner_balance';
    case UPDATE_WITHDRAW_AMOUNT = 'update_withdraw_amount';
    case UPDATE_DEPOSIT_AMOUNT = 'update_deposit_amount';
    case CREATE_WITHDRAW = 'create_withdraw';
    case DELETE_PACKAGE_REINVEST_PROFIT = 'delete_package_reinvest_profit';
    case APPROVE_TRANSACTION = 'approve_transaction';
    case REJECT_TRANSACTION = 'reject_transaction';
    case MODERATE_TRANSACTION = 'moderate_transaction';
    case WITHDRAW_PACKAGE_REINVEST_PROFIT = 'withdraw_package_reinvest_profit';
    case EXTEND_PACKAGE_REINVEST_PROFIT = 'extend_package_reinvest_profit';
    case CLOSE_ITC_PACKAGE = 'close_itc_package';
    case UPDATE_REGULAR_BONUS = 'update_regular_bonus';
    case UPSERT_TOKEN_RATE = 'upsert_token_rate';
    case UPDATE_BENEFICIARY = 'update_beneficiary';
    case DELETE_BENEFICIARY = 'delete_beneficiary';

    public function label(): string
    {
        return match ($this) {
            self::UPDATE_ITC_PACKAGE_AMOUNT => __('activity/log_action.update_itc_package_amount'),
            self::UPDATE_ITC_PACKAGE_TYPE => __('activity/log_action.update_itc_package_type'),
            self::UPDATE_ITC_PACKAGE_PROFIT_PERCENT => __('activity/log_action.update_itc_package_profit_percent'),
            self::UPDATE_ITC_PACKAGE_CREATED_AT => __('activity/log_action.update_itc_package_created_at'),
            self::UPDATE_REFERRER => __('activity/log_action.update_referrer'),
            self::UPDATE_USER_RANK => __('activity/log_action.update_user_rank'),
            self::UPDATE_USER_EMAIL => __('activity/log_action.update_user_email'),
            self::UPDATE_USER_USERNAME => __('activity/log_action.update_user_username'),
            self::UPDATE_INVESTMENTS_SUM => __('activity/log_action.update_investments_sum'),
            self::UPDATE_PARTNER_BALANCE => __('activity/log_action.update_partner_balance'),
            self::UPDATE_WITHDRAW_AMOUNT => __('activity/log_action.update_withdraw_amount'),
            self::UPDATE_DEPOSIT_AMOUNT => __('activity/log_action.update_deposit_amount'),
            self::CREATE_WITHDRAW => __('activity/log_action.create_withdraw'),
            self::DELETE_PACKAGE_REINVEST_PROFIT => __('activity/log_action.delete_package_reinvest_profit'),
            self::APPROVE_TRANSACTION => __('activity/log_action.approve_transaction'),
            self::REJECT_TRANSACTION => __('activity/log_action.reject_transaction'),
            self::MODERATE_TRANSACTION => __('activity/log_action.moderate_transaction'),
            self::WITHDRAW_PACKAGE_REINVEST_PROFIT => __('activity/log_action.withdraw_package_reinvest_profit'),
            self::EXTEND_PACKAGE_REINVEST_PROFIT => __('activity/log_action.extend_package_reinvest_profit'),
            self::CLOSE_ITC_PACKAGE => __('activity/log_action.close_itc_package'),
            self::UPDATE_REGULAR_BONUS => __('activity/log_action.update_regular_bonus'),
            self::UPSERT_TOKEN_RATE => __('activity/log_action.upsert_token_rate'),
            self::UPDATE_BENEFICIARY => __('activity/log_action.update_beneficiary'),
            self::DELETE_BENEFICIARY => __('activity/log_action.delete_beneficiary'),
        };
    }
}
