<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A dedicated notification (rather than reusing Illuminate\Auth\Notifications\
 * ResetPassword) because that one builds its link via the `password.reset`
 * route name, which belongs to the storefront `users` guard - the admin
 * guard has its own broker/route (`admin.password.reset`, see Admin::
 * sendPasswordResetNotification()) and its own Arabic-only copy.
 */
class AdminResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $resetUrl) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('admin.auth.reset_mail_subject'))
            ->greeting(__('admin.auth.reset_mail_greeting', ['name' => $notifiable->name]))
            ->line(__('admin.auth.reset_mail_line'))
            ->action(__('admin.auth.reset_mail_action'), $this->resetUrl)
            ->line(__('admin.auth.reset_mail_expire', ['minutes' => (int) config('auth.passwords.admins.expire')]));
    }
}
