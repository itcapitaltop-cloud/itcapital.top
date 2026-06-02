<?php

declare(strict_types=1);

namespace App\Services\ActivityLog;

use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Models\PartnerClosure;
use App\Models\User;

final class PartnerReferralActivityService
{
    public function __construct(
        private readonly BusinessActivityLogger $activityLogger,
    ) {}

    public function logAttached(User $user, User $referrer, string $context = 'system'): void
    {
        $this->activityLogger->write(new WriteBusinessActivityData(
            type: ActivityEventTypeEnum::BecameReferralOfUser,
            userId: $user->id,
            subject: $user,
            feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
            properties: [
                'username' => $referrer->username,
                'line' => 1,
            ],
            causer: $user,
            logName: 'partners',
            context: $context,
        ));

        $ancestorIds = PartnerClosure::query()
            ->where('descendant_id', $user->id)
            ->where('depth', '>', 0)
            ->orderBy('depth')
            ->get(['ancestor_id', 'depth']);

        $ancestors = User::query()
            ->whereIn('id', $ancestorIds->pluck('ancestor_id'))
            ->get(['id', 'username'])
            ->keyBy('id');

        foreach ($ancestorIds as $row) {
            $ancestor = $ancestors->get($row->ancestor_id);

            if ($ancestor === null) {
                continue;
            }

            $this->activityLogger->write(new WriteBusinessActivityData(
                type: ActivityEventTypeEnum::ReferralAddedToLine,
                userId: $ancestor->id,
                subject: $user,
                feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
                properties: [
                    'username' => $user->username,
                    'line' => (int) $row->depth,
                ],
                causer: $user,
                logName: 'partners',
                context: $context,
            ));
        }
    }
}
