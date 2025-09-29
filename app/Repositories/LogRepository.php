<?php

namespace App\Repositories;

use App\Contracts\Logs\LogRepositoryContract;
use Illuminate\Database\Eloquent\Model;
use App\Models\Transaction;
use App\Models\LogAdminAction;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LogRepository implements LogRepositoryContract
{
    public function updated(Model $model, string $actionType, array $oldValues, array $newValues, ?int $targetUseId = null): void
    {
        LogAdminAction::create([
            'admin_id'    => Auth::id(),
            'action_type' => $actionType,
            'model_type'  => get_class($model),
            'model_id'    => $model->getKey(),
            'target_user_id' => $targetUseId,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
        ]);
    }
}
