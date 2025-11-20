<?php

namespace App\Contracts;

interface ActionContract
{
    /**
     * Execute action.
     *
     * @return mixed
     */
    public function execute(): mixed;
}
