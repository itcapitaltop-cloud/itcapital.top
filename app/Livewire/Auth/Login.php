<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Models\UserAuthLog;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Login extends Component
{
    #[Validate(['required', 'credentials'])]
    public string $login = '';
    #[Validate([
        'required',
        'credentials:login'])]
    public string $password = '';

    /**
     * @throws Exception
     */
    public function submit(): void
    {
        $this->validate();

        $user = User::withoutGlobalScope('notBanned')
            ->where('username', $this->login)
            ->orWhere('email', $this->login)
            ->first();

        if (is_null($user)) {
            return;
        }

        try {
            $isValid = Hash::check($this->password, $user->password);

            if (Hash::needsRehash($user->password)) {
                $user->password = Hash::make($this->password);
            }
            $user->save();
//            Log::channel('source')->debug($isValid);
        } catch (\Throwable $e) {
//            Log::channel('source')->debug($e);
            $isValid = false;

        }

        if ($isValid) {
            if (!is_null($user->banned_at)) {
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
    }

    public function boot(): void
    {
        Validator::extend('credentials', function (
            string $attribute,
            mixed  $value,
            array  $parameters,
                   $validator
        ): bool {
            $data  = $validator->getData();

            $login = $data['login'] ?? ($data['email'] ?? null);
            $pass  = $data['password'] ?? null;

            if (!$login || !$pass) {
                return false;
            }

            $user = User::query()
                ->where('username', $login)
                ->orWhere('email', $login)
                ->first();

            return $user && Hash::check($pass, $user->password);
        });
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
