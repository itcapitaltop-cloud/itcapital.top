<?php

return [
    'deposit_address'  => env('WALLET_DEPOSIT_ADDRESS', '0x7f9d84d79DF5d25089e50A24D89F0f7C1A0125ec'),
    'withdraw_address' => env('WALLET_WITHDRAW_ADDRESS', '0x7f9d84d79DF5d25089e50A24D89F0f7C1A0125ec'),
    'network'          => env('WALLET_NETWORK', 'BEP20'),
];
