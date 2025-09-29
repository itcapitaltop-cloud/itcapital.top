<?php

namespace App\Livewire\Auth;

use App\Exceptions\Domain\EmailAlreadyExistsException;
use App\Exceptions\Domain\UserBannedException;
use App\Helpers\Notify;
use App\Models\Partner;
use App\Models\PartnerClosure;
use App\Models\User;
use App\Repositories\PartnerClosureRepository;
use App\Traits\Livewire\FormComponentTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Illuminate\Auth\Events\Registered;

class SignUp extends Component
{
    use FormComponentTrait;

    #[Validate(['required'])]
    public string $username = '';
    #[Validate(['required'])]
    public string $email = '';
    #[Validate(['required'])]
    public string $firstName = '';
    #[Validate(['required'])]
    public string $lastName = '';

    #[Validate(
        [
            'required',
            'regex:/^(?=.*\d).+$/u',
        ])]
    public string $password = '';
    #[Validate(['required'])]
    public string $passwordConfirm = '';

    public function onSubmit(): void
    {
        $candidate = User::withoutGlobalScope('notBanned')->firstWhere([
            'email' => $this->email
        ]);

        if (!is_null($candidate)) {
            if (!is_null($candidate->banned_at)) {
                throw new UserBannedException();
            }
            throw new EmailAlreadyExistsException();
        }

        $partner = null;

        if (!is_null(session()->get('partner'))) {
            $partner = User::query()->where('username', session()->get('partner'))->first();
            Notify::referralJoined($partner, $this->username);
        }

        $user = DB::transaction(function () use ($partner) {
            $user = User::query()->create([
                'username' => $this->username,
                'email' => $this->email,
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'password' => $this->password,
                'passwordConfirm' => $this->passwordConfirm
            ]);

            if (!is_null($partner)) {
                Partner::query()->create([
                    'user_id' => $user->id,
                    'partner_id' => $partner->id
                ]);
                PartnerClosureRepository::add($partner->id,$user->id);
            }
            else {
                PartnerClosure::firstOrCreate(['ancestor_id'=>$user->id,'descendant_id'=>$user->id,'depth'=>0]);
            }
            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        $this->redirectRoute('verification.notice');
    }

    public function exception($e, $stopPropagation)
    {
        if ($e instanceof UserBannedException) {
            $this->dispatch(
                'new-system-notification',
                type: 'error-banned',
                message: __('user_banned_1')  . '<a href="https://t.me/ITCAPITALTOP" target="_blank" class="underline text-white/80 hover:text-white">' . __('user_banned_2') . '</a>');
            $stopPropagation();
        }

        if ($e instanceof EmailAlreadyExistsException) {
            $this->dispatch('new-system-notification', type: 'error', message: 'Такая почта уже зарегистрированна');
            $stopPropagation();
        }
    }

    public function render(): View
    {
        $partner = null;

        if (!is_null(session()->get('partner'))) {
            $partner = User::query()->where('username', session()->get('partner'))->first();
        }

        return view('livewire.auth.sign-up', [
            'partner' => $partner
        ]);
    }
}
