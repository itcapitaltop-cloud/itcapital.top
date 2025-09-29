<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CacheQuotesAlphaVantage extends Command
{
    protected $signature = 'quotes:cache-alphavantage';
    protected $description = 'Кешировать котировки с AlphaVantage';

    public function handle()
    {
        $quotes = [];

        // Brent (AlphaVantage)
        $brentResponse = Http::get('https://www.alphavantage.co/query', [
            'function' => 'BRENT',
            'interval' => 'monthly',
            'apikey' => config('services.alphavantage.key'),
        ]);
        $brentData = $brentResponse->json();
        $brentRows = $brentData['data'] ?? $brentData['Data'] ?? null;
        if (is_array($brentRows) && count($brentRows) >= 2) {
            $latest = (float)$brentRows[0]['value'];
            $prev = (float)$brentRows[1]['value'];
            $change = $latest - $prev;
            $percent = $prev !== 0 ? ($change / $prev) * 100 : 0;
            $quotes[] = [
                'icon' => 'brent.svg',
                'name' => 'BRENT',
                'arrow' => $change >= 0 ? '▲' : '▼',
                'price' => number_format($latest, 2),
                'change' => ($change >= 0 ? '+' : '') . number_format($change, 2),
                'percent' => ($percent >= 0 ? '+' : '') . number_format($percent, 2) . '%',
                'change_float' => $change,
                'percent_float' => $percent,
            ];
        }

        // Natural Gas (AlphaVantage)
        $gasResponse = Http::get('https://www.alphavantage.co/query', [
            'function' => 'NATURAL_GAS',
            'interval' => 'monthly',
            'apikey' => config('services.alphavantage.key'),
        ]);
        $gasData = $gasResponse->json();
        $gasRows = $gasData['data'] ?? $gasData['Data'] ?? null;
        if (is_array($gasRows) && count($gasRows) >= 2) {
            $latest = (float)$gasRows[0]['value'];
            $prev = (float)$gasRows[1]['value'];
            $change = $latest - $prev;
            $percent = $prev !== 0 ? ($change / $prev) * 100 : 0;
            $quotes[] = [
                'icon' => 'gas.svg',
                'name' => 'NATURAL GAS',
                'arrow' => $change >= 0 ? '▲' : '▼',
                'price' => number_format($latest, 2),
                'change' => ($change >= 0 ? '+' : '') . number_format($change, 2),
                'percent' => ($percent >= 0 ? '+' : '') . number_format($percent, 2) . '%',
                'change_float' => $change,
                'percent_float' => $percent,
            ];
        }

        Cache::put('quotes.marquee.alphavantage', $quotes, now()->addDay());
        $this->info('AlphaVantage quotes cached!');
    }
}
