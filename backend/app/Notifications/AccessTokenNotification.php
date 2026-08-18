<?php

namespace App\Notifications;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccessTokenNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Student $student, public string $plainToken, public string $expiresAt)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Token akses Smart Learning')
            ->greeting('Halo Pendamping ' . $this->student->nama)
            ->line('Gunakan token berikut untuk masuk ke aplikasi pembelajaran:')
            ->line($this->plainToken)
            ->line('Token berlaku sampai ' . $this->expiresAt . '.')
            ->line('Jangan bagikan token ini kepada pihak lain.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['student_id' => $this->student->id, 'expires_at' => $this->expiresAt];
    }
}
