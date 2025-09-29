<?php
namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as Base;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class ResetPasswordRu extends Base
{
    /** Полностью переопределяем письмо */
    public function toMail($notifiable)
    {
        // Генерируем ту же ссылку, что делает оригинальный класс
        $url = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject(__('email_password_reset_title'))
            ->view('emails.password-reset-ru', [
                'url'   => $url,
                'token' => $this->token,
            ]);
    }

    /** Берём приватную логику формирования URL из базового класса */
    protected function resetUrl($notifiable): string
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
