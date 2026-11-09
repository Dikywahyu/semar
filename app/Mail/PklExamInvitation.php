<?php

namespace App\Mail;

use App\Models\Pkl\PengajuanPkl;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PklExamInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PengajuanPkl $pengajuan, public string $recipientName)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Undangan Ujian PKL - ' . ($this->pengajuan->mahasiswaRegistrasi?->nama_mahasiswa ?? 'Mahasiswa'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'pkl.emails.invitation');
    }
}
