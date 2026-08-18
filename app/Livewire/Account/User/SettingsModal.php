<?php

namespace App\Livewire\Account\User;

use App\Actions\User\InvalidateSessionUserAction;
use App\Models\Beneficiary;
use App\Notifications\PasswordChanged;
use App\Notifications\VerifyNewEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;

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

    #[Validate(['required', 'string', 'max:255'])]
    public string $beneficiaryFullName = '';

    #[Validate(['required', 'string', 'regex:/^[0-9+()\-\s]{7,32}$/', 'max:32'])]
    public string $beneficiaryPhone = '';

    #[Validate(['required', 'url:http,https', 'max:2048'])]
    public string $beneficiarySocialUrl = '';

    public ?int $editingBeneficiaryId = null;

    public ?int $deletingBeneficiaryId = null;

    public string $deletingBeneficiaryName = '';

    public function mount(): void
    {
        $u = Auth::user();

        $this->first_name = $u->first_name;
        $this->last_name = $u->last_name;
        $this->telegram = $u->telegram ?? '';
        $this->locale = $u->locale ?? session('locale') ?? (string) config('app.locale', 'ru');
        $this->email = $u->pending_email ?: $u->email;
        $this->originalEmail = $u->email;

    }

    /**
     * @throws ValidationException
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function save()
    {
        $u = Auth::user();
        $passwordChanged = false;

        $this->validateOnly('first_name');
        $this->validateOnly('last_name');
        $this->validateOnly('telegram');
        $this->validateOnly('locale');
        $this->validateOnly('email');

        $u->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'telegram' => $this->telegram,
            'locale' => $this->locale,
        ]);

        if ($this->newPassword !== '') {
            $this->validateOnly('newPassword');
            $this->validateOnly('newPasswordConfirm');
            $u->update(['password' => Hash::make($this->newPassword)]);

            new InvalidateSessionUserAction($u)->execute();

            $u->notify(new PasswordChanged());

            $passwordChanged = true;
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

        if ($passwordChanged) {
            Auth::logout();
            session()?->invalidate();
            session()?->regenerateToken();

            return redirect()->route('login');
        }

        return redirect(request()->header('Referer'));
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

    public function saveBeneficiary(): void
    {
        $validated = $this->validate(
            [
                'beneficiaryFullName' => ['required', 'string', 'max:255'],
                'beneficiaryPhone' => ['required', 'string', 'regex:/^[0-9+()\-\s]{7,32}$/', 'max:32'],
                'beneficiarySocialUrl' => ['required', 'url:http,https', 'max:2048'],
            ],
            [
                'beneficiaryFullName.required' => __('livewire_user_settings_beneficiary_full_name_required'),
                'beneficiaryFullName.string' => __('livewire_user_settings_beneficiary_full_name_invalid'),
                'beneficiaryFullName.max' => __('livewire_user_settings_beneficiary_full_name_invalid'),
                'beneficiaryPhone.required' => __('livewire_user_settings_beneficiary_phone_required'),
                'beneficiaryPhone.string' => __('livewire_user_settings_beneficiary_phone_invalid'),
                'beneficiaryPhone.regex' => __('livewire_user_settings_beneficiary_phone_invalid'),
                'beneficiaryPhone.max' => __('livewire_user_settings_beneficiary_phone_invalid'),
                'beneficiarySocialUrl.required' => __('livewire_user_settings_beneficiary_social_url_required'),
                'beneficiarySocialUrl.url' => __('livewire_user_settings_beneficiary_social_url_invalid'),
                'beneficiarySocialUrl.max' => __('livewire_user_settings_beneficiary_social_url_invalid'),
            ],
            attributes: [
                'beneficiaryFullName' => __('livewire_user_settings_beneficiary_full_name'),
                'beneficiaryPhone' => __('livewire_user_settings_beneficiary_phone'),
                'beneficiarySocialUrl' => __('livewire_user_settings_beneficiary_social_url'),
            ],
        );

        $data = [
            'full_name' => $validated['beneficiaryFullName'],
            'phone' => $validated['beneficiaryPhone'],
            'social_url' => $validated['beneficiarySocialUrl'],
        ];

        if ($this->editingBeneficiaryId !== null) {
            $beneficiary = Beneficiary::query()->findOrFail($this->editingBeneficiaryId);
            abort_unless($beneficiary->user_id === Auth::id(), 403);
            $beneficiary->update($data);
            $message = __('livewire_user_settings_beneficiary_updated');
        } else {
            Beneficiary::query()->create(['user_id' => Auth::id(), ...$data]);
            $message = __('livewire_user_settings_beneficiary_saved');
        }

        $this->resetBeneficiaryForm();
        $this->dispatch('beneficiary-saved');
        $this->dispatch(
            'new-system-notification',
            type: 'success',
            message: $message,
        );
    }

    public function startAddingBeneficiary(): void
    {
        $this->resetBeneficiaryForm();
        $this->dispatch('beneficiary-form-opened');
    }

    public function startEditingBeneficiary(int $beneficiaryId): void
    {
        $beneficiary = Beneficiary::query()->findOrFail($beneficiaryId);
        abort_unless($beneficiary->user_id === Auth::id(), 403);

        $this->editingBeneficiaryId = $beneficiary->id;
        $this->beneficiaryFullName = $beneficiary->full_name;
        $this->beneficiaryPhone = $beneficiary->phone;
        $this->beneficiarySocialUrl = $beneficiary->social_url;
        $this->resetValidation();
        $this->dispatch('beneficiary-form-opened');
    }

    public function startDeletingBeneficiary(int $beneficiaryId): void
    {
        $beneficiary = Beneficiary::query()->findOrFail($beneficiaryId);
        abort_unless($beneficiary->user_id === Auth::id(), 403);

        $this->deletingBeneficiaryId = $beneficiary->id;
        $this->deletingBeneficiaryName = $beneficiary->full_name;
        $this->dispatch('beneficiary-delete-confirmation-opened');
    }

    public function deleteBeneficiary(): void
    {
        abort_if($this->deletingBeneficiaryId === null, 404);

        $beneficiary = Beneficiary::query()->findOrFail($this->deletingBeneficiaryId);
        abort_unless($beneficiary->user_id === Auth::id(), 403);

        $beneficiary->delete();
        $this->reset('deletingBeneficiaryId', 'deletingBeneficiaryName');
        $this->dispatch('beneficiary-deleted');
        $this->dispatch(
            'new-system-notification',
            type: 'success',
            message: __('livewire_user_settings_beneficiary_deleted'),
        );
    }

    private function resetBeneficiaryForm(): void
    {
        $this->reset(
            'editingBeneficiaryId',
            'beneficiaryFullName',
            'beneficiaryPhone',
            'beneficiarySocialUrl',
        );
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.account.user.settings-modal', [
            'pendingEmail' => Auth::user()->pending_email,
            'beneficiaries' => Auth::user()->beneficiaries()->latest()->get(),
        ]);
    }
}
