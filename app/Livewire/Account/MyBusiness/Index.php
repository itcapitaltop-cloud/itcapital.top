<?php

namespace App\Livewire\Account\MyBusiness;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public int $line = 1;

    public function getPartnersByLine(): Collection
    {
        $ids = [Auth::id()];

        $partners = collect([]);

        for ($i = 0; $i < $this->line; $i++) {
            $partners = Partner::with(['user' => function ($query) {
                $query->withoutGlobalScope('notBanned');
            }])
                ->whereIn('partner_id', $ids)
                ->whereHas('user', function ($query) {
                    $query->whereNull('banned_at');
                })
                ->orderByDesc('created_at')
                ->get();

            $ids = $partners->map(function (Partner $partner) {
                return $partner->user_id;
            });
        }

        return $partners->map(function (Partner $partner) {
            return $partner->user;
        });
    }

    public function render()
    {
        return view('livewire.account.my-business.index', [
            'partners' => $this->getPartnersByLine(),
            'rank' => Auth::user()->rank
        ]);
    }
}
