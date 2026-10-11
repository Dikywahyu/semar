<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class SystemNotification extends Notification
{
    use Queueable;

    protected $details;

    /**
     * Bapak kirim datanya lewat sini (Constructor)
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Gunakan channel database
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Inilah data yang akan tersimpan di kolom 'data' pada tabel notifications
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'     => $this->details['title'] ?? 'Sistem Notifikasi',
            'message'   => $this->details['message'] ?? '-',
            'url'       => $this->details['url'] ?? '#',
            'image'     => Auth::user()->avatar ?? $this->details['image'],
            'type'      => $this->details['type'] ?? 'info', // misal: success, danger, warning
            'user_name' => Auth::user()->name ?? 'System',
        ];
    }
}
