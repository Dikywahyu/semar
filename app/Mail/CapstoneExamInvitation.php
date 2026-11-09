<?php

namespace App\Mail;

use App\Models\Capstone\CapstoneGroup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CapstoneExamInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CapstoneGroup $group)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Undangan Ujian Capstone Design');
    }

    public function content(): Content
    {
        return new Content(view: 'capstone.emails.invitation');
    }
}
