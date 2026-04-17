<?php

namespace App\Repositories;

use App\Contracts\Logs\LogRepositoryContract;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Services\ActivityLog\BusinessActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LogRepository implements LogRepositoryContract
{
    public function updated(
        Model $model,
        string $actionType,
        array $oldValues,
        array $newValues,
        ?int $targetUseId = null,
        array $extraProperties = [],
    ): void {
        $ownerId = $targetUseId ?? $this->resolveOwnerId($model);

        if ($ownerId === null) {
            return;
        }

        app(BusinessActivityLogger::class)->writeDescription(
            description: $actionType,
            userId: $ownerId,
            subject: $model,
            feeds: [ActivityFeedTypeEnum::UserDetailAdmin],
            properties: [
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                ...$extraProperties,
            ],
            causer: Auth::user(),
            logName: 'admin',
            context: 'admin',
        );
    }

    private function resolveOwnerId(Model $model): ?int
    {
        $userId = $model->getAttribute('user_id');

        if (is_numeric($userId)) {
            return (int) $userId;
        }

        if (method_exists($model, 'transaction')) {
            $transaction = $model->transaction()->first();

            if ($transaction !== null && is_numeric($transaction->user_id)) {
                return (int) $transaction->user_id;
            }
        }

        return null;
    }
}
