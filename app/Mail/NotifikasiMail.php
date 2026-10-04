<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotifikasiMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $judul, public ?string $isi = null, public ?string $url = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[SIPERSU FT-UMB] '.$this->judul);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.notifikasi');
    }
}
