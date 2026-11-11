<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $purposeText;
    public int $expiryMinutes;

    public function __construct(string $otp, string $purpose = 'login', int $expiryMinutes = 5)
    {
        $this->otp = $otp;
        $this->purposeText = $purpose === 'login' ? 'Login Verification' : 'Password Reset Verification';
        $this->expiryMinutes = $expiryMinutes;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "EmbryoCase – {$this->purposeText}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp_verification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
