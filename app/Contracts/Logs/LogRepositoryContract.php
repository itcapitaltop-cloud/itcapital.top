<?php

namespace App\Contracts\Logs;

use Illuminate\Database\Eloquent\Model;

interface LogRepositoryContract
{
    public function updated(
        Model $model,
        string $actionType,
        array $oldValues,
        array $newValues,
        ?int $targetUseId = null,
        array $extraProperties = [],
    ): void;
}
