<?php

namespace App\Enums\Partners;

enum PartnerRewardTypeEnum: string
{
    case START   = 'start';
    case REGULAR = 'regular';

    case STAKING_START = 'staking_start';
    case STAKING_REGULAR = 'staking_regular';
}
