<?php

namespace App\Enums\CommonFund;

use Livewire\Wireable;

enum PackageTypeEnum: string implements Wireable
{
    case Bullet = 'bullet';
    case Blitz = 'blitz';
    case Rapid = 'rapid';
    case Classical = 'classical';

    public function toLivewire()
    {
        return [
            'name' => $this->getName(),
            'icon' => $this->getIcon(),
            'description' => $this->getDescription(),
            'profitPercent' => $this->getProfitPercent(),
            'workTime' => $this->getWorkTime(),
            'type' => $this->value
        ];
    }

    public static function fromLivewire($value)
    {
        return PackageTypeEnum::from($value['type']);
    }

    public function getName(): string
    {
        return match ($this) {
            self::Bullet => 'Bullet',
            self::Blitz => 'Blitz',
            self::Rapid => 'Rapid',
            self::Classical => 'Classical',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Bullet => vite()->icon('common-fund/bullet.svg'),
            self::Blitz => vite()->icon('common-fund/blitz.svg'),
            self::Rapid => vite()->icon('common-fund/rapid.svg'),
            self::Classical => vite()->icon('common-fund/classical.svg'),
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Bullet => 'Этот пакет подходит любителям быстрых денег и скальпинга. Самый короткий срок работы позволяет оценить возможности проекта и извлечь прибыль. После окончания работы можно запустить с повышенной доходностью, как на следующем по времени работы пакете',
            self::Blitz => 'Для тех кто любит среднесрочные и прибыльные решения. Оптимальное сочетание времени и прибыли',
            self::Rapid => 'Если вы мыслите стратегически и осознаете, что качественный доход создается на дистанции, то этот пакет разработан специально для вас. Каждый опытный игрок знает, что чем меньше лишних движений, тем выше результат',
            self::Classical => 'Самые высокие прибыли возможны только при грамотном использовании времени и ресурсов. Если вы поклонник стратегии Баффета и принимаете долгосрочные решения, то этот пакет идеально вам подходит',
        };
    }

    public function getProfitPercent(): string
    {
        return match ($this) {
            self::Bullet => '6.5',
            self::Blitz => '8.2',
            self::Rapid => '10',
            self::Classical => '12',
        };
    }

    public function getWorkTime(): string
    {
        return match ($this) {
            self::Bullet => '10',
            self::Blitz => '20',
            self::Rapid => '30',
            self::Classical => '50',
        };
    }
}
