<?php

declare(strict_types=1);

namespace App\Livewire\App;

use Illuminate\View\View;
use Livewire\Component;

final class LanguageSwitcher extends Component
{
    public bool $open = false;

    public function switch(string $locale)
    {
        session(['locale' => $locale]);

        if (auth()->check()) {
            auth()
                ->user()
                ->update([
                    'locale' => $locale,
                ]);
        }

        return redirect(request()->header('Referer'));
    }

    public function render(): View
    {
        return view('livewire.app.language-switcher', [
            'locale' => app()->getLocale(),
        ]);
    }
}
