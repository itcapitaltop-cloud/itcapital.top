<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function index(Request $request)
    {
        if (!is_null($request->query('partner'))) {
            $request->session()->put('partner', $request->query('partner'));
        }

        return view('pages.index');
    }
}
