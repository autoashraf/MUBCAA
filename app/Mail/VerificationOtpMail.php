<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public object $user,
        public string $code,
        public string $purpose = 'verification',
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject($this->purpose === 'login' ? 'Your MUBCAA sign-in code' : 'Your MUBCAA verification code')
            ->view('emails.verification-otp');
    }
}
