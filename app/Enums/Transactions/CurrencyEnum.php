<?php

namespace App\Enums\Transactions;

use InvalidArgumentException;

enum CurrencyEnum: string
{
    case USDT_BEP_20     = 'usdt_bep_20';      // BNB Smart Chain
    case USDT_ERC_20     = 'usdt_erc_20';      // Ethereum
    case USDT_POLYGON    = 'usdt_polygon';     // Polygon
    case USDT_ARBITRUM   = 'usdt_arbitrum';    // Arbitrum One
    case USDT_OPTIMISM   = 'usdt_optimism';    // Optimism
    case USDT_AVALANCHE  = 'usdt_avalanche';   // Avalanche C‑Chain
    case USDT_FANTOM     = 'usdt_fantom';      // Fantom Opera
    case USDT_BASE       = 'usdt_base';        // Base Mainnet

    /* ───────── non‑EVM ───────── */
    case USDT_TRC_20     = 'usdt_trc_20';      // Tron
    case USDT_SOLANA     = 'usdt_solana';      // Solana SPL

    private const MAP = [
        /* EVM‑сетевые */
        'BEP20'     => self::USDT_BEP_20,
        'ERC20'     => self::USDT_ERC_20,
        'POLYGON'   => self::USDT_POLYGON,
        'ARBITRUM'  => self::USDT_ARBITRUM,
        'OPTIMISM'  => self::USDT_OPTIMISM,
        'AVALANCHE' => self::USDT_AVALANCHE,
        'FANTOM'    => self::USDT_FANTOM,
        'BASE'      => self::USDT_BASE,

        /* non‑EVM */
        'TRC20'     => self::USDT_TRC_20,
        'SOLANA'    => self::USDT_SOLANA,
    ];

    public static function fromNetwork(string $network): self
    {
        $key = strtoupper($network);

        return self::MAP[$key]
            ?? throw new InvalidArgumentException("Unsupported network: {$network}");
    }
}
