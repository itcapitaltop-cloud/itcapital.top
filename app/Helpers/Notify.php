<?php

namespace App\Helpers;

use App\Models\User;
use App\Notifications\InAppNotification;

class Notify
{
    public static function dividends(User $user, string $amount, string $packageCreated, string $packageType, string $dividendsUuid): void
    {
        $icon = vite()->icon('currency/itc.svg');
        $amountEsc = e($amount);
        $typeEsc = e($packageType);
        $dateEsc = e($packageCreated);
        $uuidEsc = e($dividendsUuid);

        $title = "<span class='inline-flex items-center gap-[4px] whitespace-nowrap'>Вам начислено <img src='{$icon}' alt='' class='inline-block w-[8px] align-[-2px]' />{$amountEsc} дивидендов на пакет</span>";

        $message = "<span class='block'>{$typeEsc} {$dateEsc}</span>";

        $user->notify(new InAppNotification(
            title: $title,
            message: $message,
            icon: 'notifications/dividends.svg',
            action: ['type' => 'call', 'name' => 'reinvest', 'params' => ['uuid' => $uuidEsc]],
            buttonText: 'Реинвестировать',
            display: 'block',
        ));
    }

    public static function recalculateDividends(User $user, string $amount, string $packageCreated, string $packageType, string $dividendsUuid): void
    {
        $icon = vite()->icon('currency/itc.svg');
        $amountEsc = e($amount);
        $typeEsc = e($packageType);
        $dateEsc = e($packageCreated);
        $uuidEsc = e($dividendsUuid);

        $title = "
            <div class='flex flex-col leading-snug'>
                <span class='text-[13px] text-white/90'>
                    В связи с ошибкой в расчетах, вам доначислено
                </span>
                <span class='inline-flex items-center gap-1 text-[13px] font-semibold text-white'>
                    <img src='{$icon}' alt='' class='inline-block w-[8px] align-[-2px]' />
                    {$amountEsc} <span class='font-normal opacity-80'>дивидендов на пакет</span>
                </span>
            </div>
            ";

        $message = "<span class='block'>{$typeEsc} {$dateEsc}</span>";

        $user->notify(new InAppNotification(
            title: $title,
            message: $message,
            icon: 'notifications/dividends.svg',
            action: ['type' => 'call', 'name' => 'reinvest', 'params' => ['uuid' => $uuidEsc]],
            buttonText: 'Реинвестировать',
            display: 'block',
        ));
    }

    public static function welcome(User $user): void
    {
        $user->notify(new InAppNotification(
            title: 'Добро пожаловать в IT Capital!',
            message: 'Мы поможем вам приумножить ваш капитал',
            icon: 'notifications/welcome.svg',
            action: ['type' => 'route', 'name' => 'finance', 'params' => []],
            buttonText: 'Завести средства на счёт',
        ));
    }

    public static function rank(User $user, int $rank): void
    {
        // готовый фрагмент для вывода рядом с заголовком
        $badge = '<span class="flex text-[12px] items-center justify-center
                             bg-[#B4FF59] text-[#17162D] font-semibold
                             rounded-[4px] w-[16px] h-[17px] leading-none ml-1">' . $rank .
                  '</span>';

        $user->notify(new InAppNotification(
            title: 'Вы получили ранг',
            message: $badge,
            icon: 'notifications/rank.svg',
        ));
    }

    public static function rankDecreased(User $user, int $rank): void
    {
        $badge = '<span class="flex text-[12px] items-center justify-center
                             bg-[#B4FF59] text-[#17162D] font-semibold
                             rounded-[4px] w-[16px] h-[17px] leading-none ml-1">' . $rank .
                  '</span>';

        $user->notify(new InAppNotification(
            title: 'Ранг понижен',
            message: $badge,
            icon: 'notifications/rank.svg',
        ));
    }

    public static function referralJoined(User $user, string $refName): void
    {
        $nameHtml = '<span class="text-[#B4FF59] font-semibold">' . e($refName) . '</span>';

        $user->notify(new InAppNotification(
            title: 'По вашей ссылке зарегистрировался партнер',
            message: $nameHtml,
            icon: 'notifications/referral-joined.svg',
            display: 'block'
        ));
    }

    public static function depositApproved(User $user, string $amount): void
    {
        $icon = vite()->icon('currency/itc.svg');
        $amountEsc = e($amount);

        $message = "<span class='font-bold ml-1'><img src='{$icon}' alt='' class='inline-block w-[8px] align-[-2px]' /> $amountEsc</span>";

        $user->notify(new InAppNotification(
            title: 'Одобрена заявка на ввод',
            message: $message,
            icon: 'notifications/deposit-approved.svg',
        ));
    }

    public static function withdrawApproved(User $user, string $amount): void
    {
        $icon = vite()->icon('currency/itc.svg');
        $amountEsc = e($amount);

        $message = "<span class='font-bold ml-1'><img src='{$icon}' alt='' class='inline-block w-[8px] align-[-2px]' /> $amountEsc</span>";

        $user->notify(new InAppNotification(
            title: 'Исполнена заявка на вывод',
            message: $message,
            icon: 'notifications/deposit-approved.svg',
        ));
    }

    public static function packageBought(User $user, string $packageType, string $amount): void
    {
        $icon = vite()->icon('currency/itc.svg');
        $amountEsc = e($amount);
        $typeEsc = e($packageType);

        $title = "<span class='inline-flex items-center gap-[4px] whitespace-nowrap'>Вы приобрели пакет {$typeEsc} на сумму <img src='{$icon}' alt='' class='inline-block w-[8px] align-[-2px]' /><span class='font-bold'>{$amountEsc}</span></span>";

        $user->notify(new InAppNotification(
            title: $title,
            message: '',
            icon: 'notifications/package-bought.svg',
        ));
    }

    public static function packageStakingBought(User $user, string|float $amount): void
    {
        $icon = vite()->icon('currency/itc-staking.svg');
        $amountEsc = e($amount);

        $title = "<span class='inline-flex items-center gap-[4px] whitespace-nowrap'>Куплен пакет ITC коинов на сумму <img src='{$icon}' alt='' class='inline-block w-[8px] align-[-2px]' /><span class='font-bold'>{$amountEsc}</span></span>";

        $user->notify(new InAppNotification(
            title: $title,
            message: '',
            icon: 'notifications/package-bought.svg',
        ));
    }

    public static function bonusStart(User $user, string|float $amount): void
    {
        $icon = vite()->icon('currency/itc-partners.svg');
        $amountEsc = e($amount);

        $title = "<span class='inline-flex items-center gap-[4px] whitespace-nowrap'>Начислена стартовая премия в размере <img src='{$icon}' alt='' class='inline-block w-[8px] align-[-2px]' /><span class='font-bold'>{$amountEsc}</span></span>";

        $user->notify(new InAppNotification(
            title: $title,
            message: '',
            icon: 'notifications/bonus-start.svg',
        ));
    }

    public static function bonusRegular(User $user, string $amount): void
    {
        $icon = vite()->icon('currency/itc-partners.svg');
        $amountEsc = e($amount);

        $title = "<span class='inline-flex items-center gap-[4px] whitespace-nowrap'>Начислена регулярная премия в размере <img src='{$icon}' alt='' class='inline-block w-[8px] align-[-2px]' /><span class='font-bold'>{$amountEsc}</span></span>";

        $user->notify(new InAppNotification(
            title: $title,
            message: '',
            icon: 'notifications/bonus-start.svg',
        ));
    }

    public static function bonusStaking(User $user, float $amount): void
    {
        $icon = vite()->icon('currency/itc-staking.svg');
        $amountEsc = e($amount);

        $title = "
            <div class='flex flex-col leading-snug'>
                <span class='text-[13px] text-white/90'>
                    Начислена доходность на пакет коинов в размере
                </span>
                <span class='inline-flex items-center gap-1 text-[13px] font-semibold text-white'>
                    <img src='{$icon}' alt='' class='inline-block w-[8px] align-[-2px]' />
                    {$amountEsc}
                </span>
            </div>
            ";

        $user->notify(new InAppNotification(
            title: $title,
            message: '',
            icon: 'notifications/bonus-start.svg',
        ));
    }
}
