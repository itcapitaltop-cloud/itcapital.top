<?php

namespace App\Http\Controllers;

use App\Enums\CommonFund\PackageTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CommonFundController extends Controller
{
    public function buyPackage()
    {
//        Log::channel('source')->debug('byPackage');
        return view('pages.account.common-fund.buy');
    }
}
