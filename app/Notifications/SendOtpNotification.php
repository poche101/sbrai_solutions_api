<?php

namespace App\Notifications;

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SendOtpNotification extends Notification
{
    protected $otp;

    public function __construct($otp) {
        $this->otp = $otp;
    }

    public function via($notifiable): array {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage {
        return (new MailMessage)
            ->subject('Verify Your Email')
            ->line('Your verification code is:')
            ->line($this->otp)
            ->line('If you did not request this, please ignore this email.');
    }
}
