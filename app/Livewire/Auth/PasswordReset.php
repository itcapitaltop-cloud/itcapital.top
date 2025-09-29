<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class PasswordReset extends Component
{
    public string $token;
    public string $email   = '';
    #[Validate(
        [
            'required',
            'regex:/^(?=.*\d).+$/u',
            'min:8',
            'confirmed'
        ])]
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    protected array $rules = [
        'email'                  => 'required|email:rfc,dns',
        'password_confirmation'  => 'required|min:8',
    ];

    public function submit(): void
    {
        $this->validate();

        $status = Password::reset(
            [
                'email'                 => $this->email,
                'password'              => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token'                 => $this->token,
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

//                event(new PasswordResetEvent($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('status', 'password-reset');
            $this->redirectRoute('login');
        }

        $this->addError('email', __($status));
    }

    public function render()
    {
        return view('livewire.auth.password-reset');
    }
}
