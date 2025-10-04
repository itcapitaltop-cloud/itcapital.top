<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use App\Models\UserAuthLog;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class Login extends Component
{
    #[Validate(['required', 'string'])]
    public string $login = '';

    #[Validate(['required', 'string'])]
    public string $password = '';

    /**
     * @throws Exception
     */
    public function submit(): void
    {
        $this->validate();

        $login = trim($this->login);

        $user = User::withoutGlobalScope('notBanned')
            ->where('username', $login)
            ->orWhere('email', $login)
            ->first();

        if (is_null($user)) {
            $this->addError('login', 'Неверный логин или пароль');

            return;
        }

        try {
            $isValid = Hash::check($this->password, $user->password);

            if (Hash::needsRehash($user->password)) {
                $user->password = Hash::make($this->password);
            }
            $user->save();
        } catch (\Throwable $e) {
            $isValid = false;

        }

        if (! $isValid) {
            $this->addError('login', 'Неверный логин или пароль');

            return;
        }

        if (! is_null($user->banned_at)) {
            throw new Exception('Пользователь забанен');
        }

        Auth::login($user, true);
        session()->regenerate();

        $agent = new Agent();
        $device = $agent->device() ?: $agent->platform() ?: 'Unknown device';
        $deviceType = $agent->deviceType();
        Log::channel('source')->debug($this->login);

        if ($deviceType) {
            $device .= ' (' . $deviceType . ')';
        }

        UserAuthLog::create([
            'user_id' => $user->id,
            'ip' => request()->ip(),
            'device' => $device,
            'browser' => $agent->browser() . ' ' . $agent->version($agent->browser()),
            'created_at' => now(),
        ]);

        $this->redirectRoute('dashboard');
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
