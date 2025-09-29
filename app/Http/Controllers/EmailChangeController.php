<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class EmailChangeController extends Controller
{
    public function __invoke(Request $request, User $user, string $hash)
    {
        abort_unless(hash_equals($hash, sha1($user->pending_email ?? '')), 403);

        $user->forceFill([
            'email'         => $user->pending_email,
            'pending_email' => null,
            'email_verified_at' => now(),
        ])->save();

        return redirect()->route('dashboard')   // куда ведёте после входа
        ->with('status', 'Новый e‑mail подтверждён');
    }
}
