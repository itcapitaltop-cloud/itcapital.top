<?php
namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as Base;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;

class VerifyEmailRu extends Base
{
    /**
     * Полностью переопределяем метод.
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return $this->buildMailMessage($verificationUrl);
    }

    /**
     * Генерация URL с подписью, как в оригинальном классе.
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    /**
     * Собираем письмо на основе собственного Blade‑шаблона.
     */
    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject(__('email_confirmation'))
            ->view('emails.verify-ru', ['url' => $url]);
    }
}
