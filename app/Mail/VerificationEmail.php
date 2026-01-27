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
    public $verificationUrl;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->verificationUrl = url("/api/auth/verify/{$user->verification_token}");
    }

    public function build()
    {
        return $this->subject('Verify Your Email - English Proficiency Test System')
                    ->view('emails.verification')
                    ->with([
                        'user' => $this->user,
                        'verificationUrl' => $this->verificationUrl
                    ]);
    }
}