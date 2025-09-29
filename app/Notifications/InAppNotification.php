<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InAppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $title,
        public string $message,
        public string $icon,
        public ?array $action = null,
        public ?string $buttonText = null,
        public ?string $display = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
//        Log::channel('source')->debug('via() called', [
//            'notifiable_id' => $notifiable->getKey(),
//            'channels' => ['database','broadcast'],
//        ]);

        return ['database','broadcast'];
    }

    public function toDatabase($notifiable): array
    {
//        Log::channel('source')->debug('toDatabase() called', [
//            'notifiable_id' => $notifiable->getKey(),
//            'title' => $this->title,
//        ]);

        return [
            'title'       => $this->title,
            'message'     => $this->message,
            'icon'        => $this->icon,
            'action'      => $this->action,
            'button_text' => $this->buttonText,
            'display'     => $this->display,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
//        Log::channel('source')->debug('toBroadcast() called', [
//            'notifiable_id' => $notifiable->getKey(),
//            'title' => $this->title,
//        ]);

        // Отправляем те же данные для клиента
        return new BroadcastMessage([
            'id'          => (string) Str::uuid(),
            'title'       => $this->title,
            'message'     => $this->message,
            'icon'        => $this->icon,
            'action'      => $this->action,
            'display'     => $this->display,
            'button_text' => $this->buttonText,
            'created_at'  => now()->toISOString(),
        ]);
    }
}
