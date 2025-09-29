<?php

namespace App\Traits\Livewire;

trait CanCatchExceptionTrait
{
    public function exception($e, $stopPropagation): void
    {
        if ($e instanceof \Exception)
        {
            $this->dispatch('new-system-notification', type: 'error', message: $e->getMessage());
        }
        $stopPropagation();
    }

    abstract function dispatch($event, ...$params);
}
