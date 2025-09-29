<?php

namespace App\View\Components\Data;

use App\Enums\Transactions\TransactionStatusEnum;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TrxStatus extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public TransactionStatusEnum $trxStatus
    )
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.data.trx-status');
    }
}
