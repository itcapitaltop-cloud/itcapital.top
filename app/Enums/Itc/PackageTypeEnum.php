<?php

namespace App\Enums\Itc;

enum PackageTypeEnum: string
{
    case STANDARD = 'standard';
    case PRIVILEGE = 'privilege';
    case VIP = 'vip';
    case ARCHIVE = 'archive';
    case PRESENT   = 'present';

    public function getName(): string
    {
        return match ($this) {
            self::PRIVILEGE => 'Privilege',
            self::STANDARD => 'Standard',
            self::VIP => 'Vip',
            self::PRESENT   => 'Present',
            self::ARCHIVE   => 'Archive',
        };
    }

    public function getCAPSName(): string
    {
        return match ($this) {
            self::PRIVILEGE => 'PRIVILEGE',
            self::STANDARD => 'STANDARD',
            self::VIP => 'VIP',
            self::PRESENT   => 'PRESENT',
            self::ARCHIVE   => 'ARCHIVE',
        };
    }

    public function getTextColor(): string
    {
        return match ($this) {
            self::STANDARD => 'text-white',
            self::PRIVILEGE, self::VIP => 'text-yellow',
            self::PRESENT   => 'text-green',
            self::ARCHIVE   => 'text-black',
        };
    }

    public function getBorderColor(): string
    {
        return match ($this) {
            self::STANDARD => 'from-blue',
            self::PRIVILEGE, self::VIP => 'from-yellow',
            self::PRESENT   => 'from-green',
            self::ARCHIVE   => 'from-black',
        };
    }
}
