<?php

declare(strict_types=1);

namespace App\Services\ActivityLog;

use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Models\BusinessActivity;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

final class BusinessActivityLogger
{
    public function write(WriteBusinessActivityData $data): ?BusinessActivity
    {
        $logger = activity($data->logName)
            ->performedOn($data->subject)
            ->event($data->context)
            ->withProperties($data->properties + [
                'type' => $data->type->value,
                'feeds' => $data->normalizedFeeds(),
            ])
            ->tap(function (BusinessActivity $activity) use ($data): void {
                $activity->user_id = $data->userId;
            });

        if ($data->causer !== null) {
            $logger->causedBy($data->causer);
        }

        if ($data->occurredAt !== null) {
            $logger->createdAt($data->occurredAt);
        }

        /** @var \App\Models\BusinessActivity|null $activity */
        $activity = $logger->log($data->type->value);

        return $activity;
    }

    /**
     * @param array<int, \App\Enums\Activity\ActivityFeedTypeEnum|string> $feeds
     * @param array<string, mixed> $properties
     */
    public function writeDescription(
        string $description,
        int $userId,
        Model $subject,
        array $feeds = [],
        array $properties = [],
        Model|int|string|null $causer = null,
        string $logName = 'default',
        string $context = 'system',
        ?CarbonInterface $occurredAt = null,
    ): ?BusinessActivity {
        $logger = activity($logName)
            ->performedOn($subject)
            ->event($context)
            ->withProperties($properties + [
                'feeds' => array_values(array_map(
                    static fn (ActivityFeedTypeEnum|string $feed): string => $feed instanceof ActivityFeedTypeEnum
                        ? $feed->value
                        : $feed,
                    $feeds
                )),
            ])
            ->tap(function (BusinessActivity $activity) use ($userId): void {
                $activity->user_id = $userId;
            });

        if ($causer !== null) {
            $logger->causedBy($causer);
        }

        if ($occurredAt !== null) {
            $logger->createdAt($occurredAt);
        }

        /** @var \App\Models\BusinessActivity|null $activity */
        $activity = $logger->log($description);

        return $activity;
    }
}
