<?php

namespace App\View\Components\Account\Itc;

use App\Models\ItcPackage;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Component;

class PackageModal extends Component
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
        return view('components.account.itc.package-modal');
    }

    public function displayName(): string
    {
        return $this->package->packageDefinition?->name ?? $this->package->type->getName();
    }

    public function cardImagePath(): ?string
    {
        $path = $this->package->packageDefinition?->card_image_path;

        if ($path === null) {
            Log::debug('[AccountItcPackageModal.cardImagePath] fallback to type image', [
                'package_id' => $this->package->id,
                'package_uuid' => $this->package->uuid,
                'package_type' => $this->package->type->value,
                'package_definition_id' => $this->package->package_definition_id,
            ]);
        }

        return $path;
    }
}
