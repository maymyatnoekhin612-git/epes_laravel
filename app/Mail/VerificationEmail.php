<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationCode;
    public $expiryMinutes = 15;

    public function __construct(User $user, $verificationCode)
    {
        $this->user = $user;
        $this->verificationCode = $verificationCode;
    }

    public function build()
    {
        return $this->subject('Email Verification Code - English Proficiency Test System')
                    ->view('emails.verification')
                    ->with([
                        'user' => $this->user,
                        'verificationCode' => $this->verificationCode,
                        'expiryMinutes' => $this->expiryMinutes
                    ]);
    }
}