<?php

namespace App\View\Components\Data;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Desc extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $name
    )
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.data.desc');
    }
}
