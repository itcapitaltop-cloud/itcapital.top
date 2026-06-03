<?php

namespace App\Livewire\Index;

use App\Models\Package\PackageDefinition;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Main extends Component
{
    public function render(): View
    {
        return view('livewire.index.main', [
            'packageDefinitions' => PackageDefinition::query()
                ->active()
                ->orderByDesc('sort_order')
                ->orderBy('id')
                ->get(),
            'reviewAverageRating' => Review::averageRating(),
            'reviewTotalCount' => Review::approvedCount(),
        ]);
    }
}
