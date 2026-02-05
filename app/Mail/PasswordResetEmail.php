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
    public $resetCode;
    public $expiryMinutes = 15;

    public function __construct(User $user, $resetCode)
    {
        $this->user = $user;
        $this->resetCode = $resetCode;
    }

    public function build()
    {
        return $this->subject('Password Reset Code - English Proficiency Test System')
                    ->view('emails.password-reset')
                    ->with([
                        'user' => $this->user,
                        'resetCode' => $this->resetCode,
                        'expiryMinutes' => $this->expiryMinutes
                    ]);
    }
}