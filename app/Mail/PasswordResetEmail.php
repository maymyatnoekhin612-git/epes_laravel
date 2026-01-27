<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $resetUrl;

    public function __construct(User $user)
    {
        $this->user = $user;
        // Point to your FRONTEND reset password page
        $this->resetUrl = env('FRONTEND_URL', 'http://localhost:5173') . '/reset-password?token=' . $user->reset_password_token;
    }

    public function build()
    {
        return $this->subject('Password Reset Request - English Proficiency Test System')
                    ->view('emails.password-reset')
                    ->with([
                        'user' => $this->user,
                        'resetUrl' => $this->resetUrl
                    ]);
    }
}