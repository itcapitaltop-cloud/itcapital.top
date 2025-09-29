<?php

namespace App\View\Components\Account\Itc;

use App\Models\ItcPackage;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PackageModal extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ItcPackage $package
    )
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.account.itc.package-modal');
    }
}
