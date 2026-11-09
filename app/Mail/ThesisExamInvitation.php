<?php

namespace App\Mail;

use App\Models\TugasAkhir\ThesisApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ThesisExamInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ThesisApplication $application, public string $examType = 'Seminar Proposal')
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Undangan ' . $this->examType . ' Tugas Akhir');
    }

    public function content(): Content
    {
        return new Content(view: 'tugas_akhir.emails.sempro_invitation');
    }
}
