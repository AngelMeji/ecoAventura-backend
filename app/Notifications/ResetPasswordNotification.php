<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
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
        // Genera el link apuntando al FRONTEND (no al backend de Laravel)
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));

        $resetUrl = $frontendUrl . '/reset-password'
            . '?token=' . $this->token
            . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Recuperación de Contraseña - EcoAventura')
            ->greeting('¡Hola, ' . $notifiable->name . '!')
            ->line('Recibiste este correo porque solicitaste restablecer la contraseña de tu cuenta.')
            ->action('Restablecer Contraseña', $resetUrl)
            ->line('Este enlace expirará en 60 minutos.')
            ->line('Si no solicitaste este cambio, puedes ignorar este correo.')
            ->salutation('EcoAventura 🌿');
    }
}
