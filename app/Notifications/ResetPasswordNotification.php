<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $resetUrl = rtrim($frontendUrl, '/') . '/reset-password?token=' . urlencode($this->token) . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Restablecer contraseña')
            ->greeting('Hola,')
            ->line('Recibiste este correo porque se solicitó un restablecimiento de contraseña para tu cuenta.')
            ->action('Restablecer contraseña', $resetUrl)
            ->line('Este enlace de restablecimiento expira en 60 minutos.')
            ->line('Si no solicitaste el restablecimiento, no es necesario realizar ninguna acción.');
    }
}
