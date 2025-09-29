<?php

namespace App\Contracts\Logs;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;

interface LogRepositoryContract
{
    public function updated(Model $model, string $actionType, array $oldValues, array $newValues, ?int $targetUseId = null): void;
}
