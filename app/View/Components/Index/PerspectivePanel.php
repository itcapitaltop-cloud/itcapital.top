<?php

namespace App\View\Components\Index;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PerspectivePanel extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $gradientClass = ''
    ) {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.index.perspective-panel');
    }
}
