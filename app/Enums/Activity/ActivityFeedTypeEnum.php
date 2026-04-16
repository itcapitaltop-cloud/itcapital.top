<?php

declare(strict_types=1);

namespace App\Enums\Activity;

enum ActivityFeedTypeEnum: string
{
    case Finance = 'finance';
    case Packages = 'packages';
    case Partners = 'partners';
    case Staking = 'staking';
    case UserDetailUser = 'user_detail_user';
    case UserDetailAdmin = 'user_detail_admin';
}
