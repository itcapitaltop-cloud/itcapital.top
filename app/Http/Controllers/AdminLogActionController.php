<?php

namespace App\Http\Controllers;

use App\Models\LogAdminAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLogActionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'action_type' => 'required|string',
            'model_type'  => 'nullable|string',
            'model_id'    => 'nullable|integer',
            'old_values'  => 'nullable|array',
            'new_values'  => 'nullable|array',
        ]);

        $data['admin_id'] = Auth::id();

        LogAdminAction::create($data);

        return response()->json(['status' => 'ok']);
    }
}
