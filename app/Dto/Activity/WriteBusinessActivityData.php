<?php

declare(strict_types=1);

namespace App\Dto\Activity;

use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

final readonly class WriteBusinessActivityData
{
    /**
     * @param array<int, \App\Enums\Activity\ActivityFeedTypeEnum|string> $feeds
     * @param array<string, mixed> $properties
     */
    public function __construct(
        public ActivityEventTypeEnum $type,
        public int $userId,
        public Model $subject,
        public array $feeds = [],
        public array $properties = [],
        public Model|int|string|null $causer = null,
        public string $logName = 'default',
        public string $context = 'system',
        public ?CarbonInterface $occurredAt = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function normalizedFeeds(): array
    {
        return array_values(array_map(
            static fn (ActivityFeedTypeEnum|string $feed): string => $feed instanceof ActivityFeedTypeEnum
                ? $feed->value
                : $feed,
            $this->feeds
        ));
    }
}
