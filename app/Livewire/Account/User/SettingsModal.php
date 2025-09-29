<?php

namespace App\Livewire\Account\User;

use App\Models\User;
use App\Notifications\PasswordChanged;
use App\Notifications\VerifyNewEmail;
use Illuminate\Support\Facades\{Auth, Hash, Lang, Log, Mail};
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Notification;

class SettingsModal extends Component
{
    #[Validate(['required', 'string', 'max:100'])]
    public string $first_name = '';

    #[Validate(['required', 'string', 'max:100'])]
    public string $last_name = '';

    #[Validate(['nullable', 'regex:/^@[A-Za-z][A-Za-z0-9_]{4,31}$/u', 'max:32'])]
    public string $telegram = '';

    #[Validate(['required', 'in:ru,en,zh'])]
    public string $locale = 'ru';

    #[Validate(['required', 'email', 'max:255'])]
    public string $email = '';

    public string $originalEmail = '';

    #[Validate(
        [
            'required',
            'min:8',
            'regex:/^(?=.*\d).+$/u',
        ])]
    public string $newPassword = '';

    #[Validate(['same:newPassword', 'required'])]
    public string $newPasswordConfirm = '';

    public function mount(): void
    {
        $u = Auth::user();

        $this->first_name = $u->first_name;
        $this->last_name  = $u->last_name;
        $this->telegram   = $u->telegram ?? '';
        $this->locale     = $u->locale   ?? 'ru';
        $this->email      = $u->pending_email ?: $u->email;
        $this->originalEmail = $u->email;
    }

    /**
     * @throws ValidationException
     */
    public function save(): void
    {
        $u = Auth::user();

        $this->validateOnly('first_name');
        $this->validateOnly('last_name');
        $this->validateOnly('telegram');
        $this->validateOnly('locale');
        $this->validateOnly('email');

        $u->update([
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'telegram'   => $this->telegram,
            'locale'     => $this->locale,
        ]);

        if ($this->newPassword !== '') {
            $this->validateOnly('newPassword');
            $this->validateOnly('newPasswordConfirm');
            $u->update(['password' => Hash::make($this->newPassword)]);

            $u->notify(new PasswordChanged);
        }

        if ($this->email !== $this->originalEmail) {
            $u->update(['pending_email' => $this->email]);

            Notification::route('mail', $this->email)
                ->notify(new VerifyNewEmail($this->email, Auth::id()));

            $this->dispatch(
                'new-system-notification',
                type: 'success',
                message: __('livewire_user_settings_verification_email_sent'),
            );
        } else {
            $this->dispatch(
                'new-system-notification',
                type: 'success',
                message: __('livewire_user_settings_data_saved'),
            );
        }

        $this->reset(['newPassword', 'newPasswordConfirm']);
        $this->originalEmail = $this->email;
    }

    public function resendVerification(): void
    {
        $u = Auth::user();

        if ($u->pending_email) {
            Notification::route('mail', $this->email)
                ->notify(new VerifyNewEmail($this->email, Auth::id()));

            $this->dispatch(
                'new-system-notification',
                type: 'success',
                message: __('livewire_user_settings_verification_email_resent'),
            );
        }
    }

    public function render()
    {
        return view('livewire.account.user.settings-modal', [
            'pendingEmail' => Auth::user()->pending_email,
        ]);
    }
}
