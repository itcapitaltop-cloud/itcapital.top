<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLog\BusinessActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLogActionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action_type' => 'required|string',
            'model_type' => 'nullable|string',
            'model_id' => 'nullable|integer',
            'old_values' => 'nullable|array',
            'new_values' => 'nullable|array',
            'target_user_id' => 'nullable|integer',
        ]);

        $subject = $this->resolveSubject(
            $data['model_type'] ?? null,
            isset($data['model_id']) ? (int) $data['model_id'] : null,
        );

        $ownerId = isset($data['target_user_id'])
            ? (int) $data['target_user_id']
            : ($subject instanceof Model ? $this->resolveOwnerId($subject) : null);

        if ($ownerId !== null) {
            app(BusinessActivityLogger::class)->writeDescription(
                description: $data['action_type'],
                userId: $ownerId,
                subject: $subject ?? User::query()->findOrFail($ownerId),
                feeds: [ActivityFeedTypeEnum::UserDetailAdmin],
                properties: [
                    'old_values' => (array) ($data['old_values'] ?? []),
                    'new_values' => (array) ($data['new_values'] ?? []),
                    'model_type' => $data['model_type'] ?? null,
                    'model_id' => $data['model_id'] ?? null,
                ],
                causer: Auth::user(),
                logName: 'admin',
                context: 'admin',
            );
        }

        return response()->json(['status' => 'ok']);
    }

    private function resolveSubject(?string $modelType, ?int $modelId): ?Model
    {
        if ($modelType === null || $modelId === null || ! class_exists($modelType)) {
            return null;
        }

        $model = new $modelType();

        if (! $model instanceof Model) {
            return null;
        }

        return $model->newQuery()->find($modelId);
    }

    private function resolveOwnerId(Model $model): ?int
    {
        $userId = $model->getAttribute('user_id');

        if (is_numeric($userId)) {
            return (int) $userId;
        }

        if ($model instanceof Transaction) {
            return is_numeric($model->user_id) ? (int) $model->user_id : null;
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
