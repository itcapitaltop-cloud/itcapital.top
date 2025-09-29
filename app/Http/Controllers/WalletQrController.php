<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class WalletQrController extends Controller
{
    public function __invoke(string $address): Response
    {
        $qr = QrCode::format('png')
            ->size(800)
            ->margin(1)
            ->errorCorrection('H')
            ->merge(vite()->icon('/main/logo.png'), .25, true)
            ->generate($address);

        return response($qr)->header('Content-Type', 'image/png');
    }
}
