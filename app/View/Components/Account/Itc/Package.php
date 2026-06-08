<?php

declare(strict_types=1);

namespace App\View\Components\Account\Itc;

use App\Models\ItcPackage;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class Package extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ItcPackage $package
    ) {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.account.itc.package');
    }

    public function displayName(): string
    {
        return $this->package->packageDefinition?->name ?? $this->package->type->getName();
    }

    public function cardImagePath(): ?string
    {
        return $this->package->packageDefinition?->card_image_path;
    }
}
