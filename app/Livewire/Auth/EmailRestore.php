<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class EmailRestore extends Component
{
    public string $login = '';   // e‑mail из формы

    protected array $rules = [
        'login' => 'required|email:rfc,dns',
    ];

    /**
     * @throws ValidationException
     */
    public function submit(): void
    {
        $this->validate();

        $status = Password::sendResetLink(['email' => $this->login]);

        switch ($status) {

            case Password::RESET_LINK_SENT:
                session()->flash('status', 'reset-link-sent');
                $this->dispatch('cooldown-start', seconds: 60);
                break;

            case Password::INVALID_USER:
                throw ValidationException::withMessages([
                    'login' => [__('livewire_auth_email_restore_user_not_found')],
                ]);

            case Password::RESET_THROTTLED:
                throw ValidationException::withMessages([
                    'login' => [__('livewire_auth_email_restore_link_already_sent')],
                ]);

            default:
                throw ValidationException::withMessages([
                    'login' => [__('livewire_auth_email_restore_send_failed')],
                ]);
        }
    }

    public function render()
    {
        return view('livewire.auth.email-restore');
    }
}
