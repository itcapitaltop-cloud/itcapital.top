<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Contracts\ActionContract;
use App\Models\User;
use Illuminate\Support\Str;

final readonly class InvalidateSessionUserAction implements ActionContract
{
    public function __construct(
        private User $user,
    ) {
        // ..
    }

    public function execute(): bool
    {
        $this->user->update([
            'remember_token' => Str::random(60),
        ]);

        $this->user->increment('session_version');

        return true;
    }
}
