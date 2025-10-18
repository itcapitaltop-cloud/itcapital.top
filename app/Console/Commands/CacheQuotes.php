<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CacheQuotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotes:cache';
    protected $description = 'Кешировать котировки с внешних API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $quotes = [];

        // Twelve Data: XAU/USD, SPY, QQQ, EUR/USD
        $response = Http::get('https://api.twelvedata.com/quote', [
            'symbol' => 'XAU/USD,SPY,QQQ,EUR/USD,BTC/USD,ETH/USD',
            'apikey' => config('services.twelvedata.key'),
        ]);
        $data = $response->json();
//        Log::channel('source')->debug($data);
        // XAU/USD
        if (isset($data['XAU/USD'])) {
            $gold = $data['XAU/USD'];
            $changeFloat = (float)($gold['change'] ?? 0);
            $percentFloat = (float)($gold['percent_change'] ?? 0);
            $quotes[] = [
                'icon' => 'gold.svg',
                'name' => 'XAU/USD',
                'arrow' => $changeFloat >= 0 ? '▲' : '▼',
                'price' => number_format((float)($gold['close'] ?? 0), 2),
                'change' => ($changeFloat >= 0 ? '+' : '') . number_format($changeFloat, 2),
                'percent' => ($percentFloat >= 0 ? '+' : '') . number_format($percentFloat, 2) . '%',
                'change_float' => $changeFloat,
                'percent_float' => $percentFloat,
            ];
        }

        // SPY (ETF S&P 500)
        if (isset($data['SPY'])) {
            $spy = $data['SPY'];
            $changeFloat = (float)($spy['change'] ?? 0);
            $percentFloat = (float)($spy['percent_change'] ?? 0);
            $quotes[] = [
                'icon' => 'sp500.svg',
                'name' => 'S&P 500',
                'arrow' => $changeFloat >= 0 ? '▲' : '▼',
                'price' => number_format((float)($spy['close'] ?? 0), 2),
                'change' => ($changeFloat >= 0 ? '+' : '') . number_format($changeFloat, 2),
                'percent' => ($percentFloat >= 0 ? '+' : '') . number_format($percentFloat, 2) . '%',
                'change_float' => $changeFloat,
                'percent_float' => $percentFloat,
            ];
        }

        // QQQ (ETF NASDAQ-100)
        if (isset($data['QQQ'])) {
            $qqq = $data['QQQ'];
            $changeFloat = (float)($qqq['change'] ?? 0);
            $percentFloat = (float)($qqq['percent_change'] ?? 0);
            $quotes[] = [
                'icon' => 'nasdaq.svg',
                'name' => 'NASDAQ',
                'arrow' => $changeFloat >= 0 ? '▲' : '▼',
                'price' => number_format((float)($qqq['close'] ?? 0), 2),
                'change' => ($changeFloat >= 0 ? '+' : '') . number_format($changeFloat, 2),
                'percent' => ($percentFloat >= 0 ? '+' : '') . number_format($percentFloat, 2) . '%',
                'change_float' => $changeFloat,
                'percent_float' => $percentFloat,
            ];
        }

        // EUR/USD
        if (isset($data['EUR/USD'])) {
            $eurusd = $data['EUR/USD'];
            $changeFloat = (float)($eurusd['change'] ?? 0);
            $percentFloat = (float)($eurusd['percent_change'] ?? 0);
            $quotes[] = [
                'icon' => 'euro-currency.svg',
                'name' => 'EUR/USD',
                'arrow' => $changeFloat >= 0 ? '▲' : '▼',
                'price' => number_format((float)($eurusd['close'] ?? 0), 5),
                'change' => ($changeFloat >= 0 ? '+' : '') . number_format($changeFloat, 5),
                'percent' => ($percentFloat >= 0 ? '+' : '') . number_format($percentFloat, 2) . '%',
                'change_float' => $changeFloat,
                'percent_float' => $percentFloat,
            ];
        }

        if (isset($data['BTC/USD'])) {
            $btc = $data['BTC/USD'];
            $changeFloat = (float)($btc['change'] ?? 0);
            $percentFloat = (float)($btc['percent_change'] ?? 0);
            $quotes[] = [
                'icon' => 'btc.svg',
                'name' => 'BTC/USD',
                'arrow' => $changeFloat >= 0 ? '▲' : '▼',
                'price' => number_format((float)($btc['close'] ?? 0), 2),
                'change' => ($changeFloat >= 0 ? '+' : '') . number_format($changeFloat, 2),
                'percent' => ($percentFloat >= 0 ? '+' : '') . number_format($percentFloat, 2) . '%',
                'change_float' => $changeFloat,
                'percent_float' => $percentFloat,
            ];
        }

        // ETH/USD
        if (isset($data['ETH/USD'])) {
            $eth = $data['ETH/USD'];
            $changeFloat = (float)($eth['change'] ?? 0);
            $percentFloat = (float)($eth['percent_change'] ?? 0);
            $quotes[] = [
                'icon' => 'ethereum.svg',
                'name' => 'ETH/USD',
                'arrow' => $changeFloat >= 0 ? '▲' : '▼',
                'price' => number_format((float)($eth['close'] ?? 0), 2),
                'change' => ($changeFloat >= 0 ? '+' : '') . number_format($changeFloat, 2),
                'percent' => ($percentFloat >= 0 ? '+' : '') . number_format($percentFloat, 2) . '%',
                'change_float' => $changeFloat,
                'percent_float' => $percentFloat,
            ];
        }

        // Сохраняем массив в кеш на сутки
        Cache::put('quotes.marquee', $quotes, now()->addDay());

        $this->info('Quotes cached!');
    }
}
