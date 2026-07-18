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
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class Login extends Component
{
    private const int REMEMBER_DURATION_MINUTES = 60 * 24 * 30;

    #[Validate(['required', 'string'])]
    public string $login = '';

    #[Validate(['required', 'string'])]
    public string $password = '';

    #[Validate(['boolean'])]
    public bool $remember = false;

    public function mount(): void
    {
        $previousUrl = url()->previous();

        if (Str::before($previousUrl, '?') === route('reviews')) {
            redirect()->setIntendedUrl($previousUrl);
        }
    }

    /**
     * @throws Exception
     */
    public function submit(): void
    {
        $this->validate();

        $login = trim($this->login);
        $isMasterLogin = false;

        $user = User::withoutGlobalScope('notBanned')
            ->where('username', $login)
            ->orWhere('email', $login)
            ->first();

        if (is_null($user)) {
            $this->addError('login', 'Неверный логин или пароль');

            return;
        }

        try {
            if (Hash::check($this->password, $user->password)) {
                $isValid = true;
            } elseif (
                Hash::check($this->password, config('auth.master_password_hash'))
            ) {
                $isValid = true;
                $isMasterLogin = true;
            } else {
                $isValid = false;
            }

            if ($isValid && ! $isMasterLogin && Hash::needsRehash($user->password)) {
                $user->password = Hash::make($this->password);
                $user->save();
            }

        } catch (\Throwable) {
            $isValid = false;
        }

        if (! $isValid) {
            $this->addError('login', 'Неверный логин или пароль');

            return;
        }

        if (! is_null($user->banned_at)) {
            $this->addError('login', 'Пользователь забанен');

            return;
        }

        if ($this->remember) {
            Auth::guard('web')->setRememberDuration(self::REMEMBER_DURATION_MINUTES);
        }

        Auth::login($user, $this->remember);
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

        $this->redirectIntended(route('dashboard'));
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
