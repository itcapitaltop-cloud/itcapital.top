<?php

namespace App\Traits\Livewire;

trait FormComponentTrait
{
    public function submit()
    {
        $this->validate();
        $this->onSubmit();
    }

    abstract function validate();
    abstract function onSubmit(): void;
}
