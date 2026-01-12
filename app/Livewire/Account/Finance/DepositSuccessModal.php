<?php

declare(strict_types=1);

namespace App\Livewire\Account\Finance;

use Livewire\Component;

final class DepositSuccessModal extends Component
{
    public bool $isDepositSuccessModalActive = false;

    public array $data = [];

    public string $type = 'deposit';

    protected $listeners = [
        'deposit-created-success' => 'open',
    ];

    public function open(string $type, array $data)
    {
        $this->type = $type;
        $this->data = $data;
        $this->isDepositSuccessModalActive = true;
    }

    public function close()
    {
        $this->isDepositSuccessModalActive = false;
    }

    public function render()
    {
        return view('livewire.account.finance.deposit-success-modal');
    }
}
