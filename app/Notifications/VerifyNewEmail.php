<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as Base;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\{URL, Lang};
use Illuminate\Support\Carbon;


class VerifyNewEmail extends Base
{
    public function __construct(
        private string $newEmail,
        private int    $userId,
    ) {}

    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return $this->buildMailMessage($verificationUrl);
    }

    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'email.change.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'user'   => $this->userId,
                'hash' => sha1($this->newEmail),
            ]
        );
    }

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject(__('email_confirmation_new'))
            ->view('emails.verify-ru', ['url' => $url]);
    }
}
