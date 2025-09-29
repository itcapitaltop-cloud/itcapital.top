<?php

namespace App\Livewire\Quotes;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Marquee extends Component
{
    public array $quotes = [];

    public string $class = '';

    public function mount()
    {
        $twelvedata = Cache::get('quotes.marquee', []);
        $alphavantage = Cache::get('quotes.marquee.alphavantage', []);

        $this->quotes = array_merge($twelvedata, $alphavantage);
    }

    public function render()
    {
        return view('livewire.quotes.marquee');
    }
}
