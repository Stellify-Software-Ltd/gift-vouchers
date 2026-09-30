<?php

namespace App\Mail;

use App\Models\Voucher;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VoucherIssuedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Voucher $voucher)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your gift voucher — '.$this->voucher->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.voucher-issued',
        );
    }
}
