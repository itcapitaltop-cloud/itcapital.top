<?php

declare(strict_types=1);

namespace App\ActivityLog;

use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\LogActionTypeEnum;
use App\Models\Deposit;
use App\Models\Withdraw;
use Spatie\Activitylog\Contracts\Activity;

final class ActivityManager
{
    public function resolve(Activity $activity): string
    {
        $activityEventType = ActivityEventTypeEnum::tryFrom($activity->description);

        if ($activityEventType !== null) {
            return $this->resolveBusinessEvent($activity, $activityEventType);
        }

        $strategies = config('activity.strategies');
        $strategyClass = $strategies[$activity->description] ?? null;

        if ($strategyClass && class_exists($strategyClass)) {
            /** @var \App\Contracts\ActivityStrategyContract $strategy */
            $strategy = app($strategyClass);

            return $strategy->handle($activity);
        }

        $adminAction = LogActionTypeEnum::tryFrom($activity->description);

        if ($adminAction !== null) {
            return $adminAction->label();
        }

        return 'Неизвестное событие';
    }

    private function resolveBusinessEvent(Activity $activity, ActivityEventTypeEnum $type): string
    {
        $amount = $this->formatAmount($activity->getExtraProperty('amount'));
        $currency = (string) ($activity->getExtraProperty('currency') ?? 'ITC');
        $financeDetails = $this->resolveFinanceDetails($activity, $currency);
        $packageUuid = (string) ($activity->getExtraProperty('package_uuid')
            ?? $activity->getExtraProperty('uuid')
            ?? '');
        $username = (string) ($activity->getExtraProperty('from_username')
            ?? $activity->getExtraProperty('username')
            ?? $activity->getExtraProperty('to_username')
            ?? '');
        $line = $activity->getExtraProperty('line');
        $profit = $this->formatAmount($activity->getExtraProperty('profit', $activity->getExtraProperty('amount')));
        $stakingTokenAmount = $this->formatAmount(
            $activity->getExtraProperty('token_amount', $activity->getExtraProperty('amount'))
        );
        $stakingPurchaseRate = $this->formatRate($activity->getExtraProperty('purchase_rate'));
        $stakingExchangeRate = $this->formatRate($activity->getExtraProperty('exchange_rate'));

        return match ($type) {
            ActivityEventTypeEnum::DepositRequested => "Создана заявка на ввод {$financeDetails} на сумму {$amount}",
            ActivityEventTypeEnum::DepositApproved => "Заявка на ввод {$financeDetails} на сумму {$amount} одобрена",
            ActivityEventTypeEnum::DepositRejected => "Заявка на ввод {$financeDetails} на сумму {$amount} отклонена",
            ActivityEventTypeEnum::WithdrawRequested => "Создана заявка на вывод {$financeDetails} на сумму {$amount}",
            ActivityEventTypeEnum::WithdrawApproved => "Заявка на вывод {$financeDetails} на сумму {$amount} одобрена",
            ActivityEventTypeEnum::WithdrawRejected => "Заявка на вывод {$financeDetails} на сумму {$amount} отклонена",
            ActivityEventTypeEnum::PackagePurchased => "Куплен пакет {$packageUuid} на сумму {$amount} ITC",
            ActivityEventTypeEnum::PackageClosed => $activity->getExtraProperty('package_type') === 'staking'
                ? "Пакет стейкинга {$packageUuid} закрыт"
                : "Пакет {$packageUuid} закрыт",
            ActivityEventTypeEnum::PackageToppedUp => "В пакет {$packageUuid} добавлена сумма {$amount} ITC",
            ActivityEventTypeEnum::PackageProfitAccrued => "Получена доходность {$amount} ITC на пакет {$packageUuid}",
            ActivityEventTypeEnum::PackageProfitWithdrawn => "С пакета {$packageUuid} выведена доходность {$amount} ITC на баланс",
            ActivityEventTypeEnum::PackageReinvested => "Доходность {$amount} ITC реинвестирована в пакет {$packageUuid}",
            ActivityEventTypeEnum::PackageAmountWithdrawnToBalance => "С пакета {$packageUuid} выведена сумма {$amount} ITC на основной баланс",
            ActivityEventTypeEnum::PackageReinvestWithdrawnToBalance => "Реинвест {$amount} ITC выведен с пакета {$packageUuid} на основной баланс",
            ActivityEventTypeEnum::PresentPackageZeroed => "Подарочный пакет {$packageUuid} обнулен на сумму {$amount} ITC",
            ActivityEventTypeEnum::ReferralAddedToLine => "В линию {$line} добавлен реферал @{$username}",
            ActivityEventTypeEnum::BecameReferralOfUser => "Пользователь стал рефералом @{$username}",
            ActivityEventTypeEnum::PartnerRegularBonusReceived => "Получена регулярная премия {$amount} ITC от реферала @{$username} линии {$line}",
            ActivityEventTypeEnum::PartnerStartBonusReceived => "Получена стартовая премия {$amount} ITC от реферала @{$username} линии {$line}",
            ActivityEventTypeEnum::StakingRegularBonusReceived => "Получена регулярная премия на стейкинг {$amount} ITC от реферала @{$username}",
            ActivityEventTypeEnum::StakingStartBonusReceived => "Получена стартовая премия на стейкинг {$amount} ITC от реферала @{$username}",
            ActivityEventTypeEnum::PartnerToMainTransferred => "Сумма {$amount} ITC выведена с партнерского на основной баланс",
            ActivityEventTypeEnum::PartnerTransferSent => "Партнеру @{$username} переведена сумма {$amount} ITC с партнерского баланса",
            ActivityEventTypeEnum::PartnerTransferReceived => "Получена сумма {$amount} ITC от партнера @{$username} на основной баланс",
            ActivityEventTypeEnum::RegularBonusTransferredToPartner => "Сумма {$amount} ITC переведена с баланса регулярной премии на партнерский баланс",
            ActivityEventTypeEnum::StakingPackagePurchased => "Куплен пакет стейкинга {$packageUuid} на {$stakingTokenAmount} ITC{$stakingPurchaseRate}",
            ActivityEventTypeEnum::StakingPackageToppedUp => "В пакет стейкинга {$packageUuid} добавлено {$stakingTokenAmount} ITC{$stakingPurchaseRate}",
            ActivityEventTypeEnum::StakingProfitAccrued => "Получена доходность {$profit} ITC на пакет стейкинга {$packageUuid}{$stakingExchangeRate}",
        };
    }

    private function formatAmount(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function formatRate(mixed $rate): string
    {
        if (! is_numeric($rate)) {
            return '';
        }

        $formattedRate = rtrim(rtrim(number_format((float) $rate, 6, '.', ''), '0'), '.');

        return ' при курсе ' . $formattedRate;
    }

    private function resolveFinanceDetails(Activity $activity, string $currency): string
    {
        $subject = $activity->subject;
        $paymentSource = (string) ($activity->getExtraProperty('payment_source') ?? '');
        $bankName = (string) ($activity->getExtraProperty('bank_name') ?? '');

        if ($subject instanceof Withdraw) {
            $paymentSource = $paymentSource !== '' ? $paymentSource : (string) ($subject->paymentSource?->source ?? '');
            $bankName = $bankName !== '' ? $bankName : (string) ($subject->fiatDetail?->bank_name ?? '');
        }

        if ($subject instanceof Deposit) {
            $paymentSource = $paymentSource !== '' ? $paymentSource : (string) ($subject->paymentSource?->source ?? '');
            $bankName = $bankName !== '' ? $bankName : (string) $subject->transaction_hash;
        }

        if ($paymentSource === 'fiat') {
            return 'на банковский счёт' . ($bankName !== '' ? ', ' . $bankName : '');
        }

        return 'через криптовалюту, ' . $currency;
    }
}
