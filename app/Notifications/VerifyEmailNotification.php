<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function verificationUrl($notifiable)
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable);
        }

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        // Signed Route Hash logic
        $hash = sha1($notifiable->getEmailForVerification());
        $id = $notifiable->getKey();
        $expiration = Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60));
        
        // Let's create a temporary signed route on the backend to generate the signature
        $backendUrl = URL::temporarySignedRoute(
            'verification.verify',
            $expiration,
            [
                'id' => $id,
                'hash' => $hash,
            ]
        );

        // Parse backend query params (signature, expires) to append to frontend
        $parsedUrl = parse_url($backendUrl);
        $query = $parsedUrl['query'] ?? '';

        return $frontendUrl . '/verify-email?' . $query . '&id=' . $id . '&hash=' . $hash;
    }
}
