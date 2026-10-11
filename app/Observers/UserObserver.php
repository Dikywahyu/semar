<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Notification;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
        // // Siapkan data untuk Super Admin
        // $details = [
        //     'title'   => 'Registrasi User Baru',
        //     'message' => "User baru bernama '{$user->name}' ({$user->email}) telah mendaftar.",
        //     'url'     => url('admin/users'), // Arahkan ke manajemen user
        //     'type'    => 'info',
        //     'image'   => $user->avatar ?? 'profile/user-1.jpg', // Foto user baru tersebut
        // ];

        // // Ambil semua Super Admin
        // $admins = User::role('Super Admin')->get();

        // // Kirim notifikasi
        // Notification::send($admins, new SystemNotification($details));
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
        // // Siapkan data untuk Super Admin
        // $details = [
        //     'title'   => 'Update User ',
        //     'message' => "User bernama '{$user->name}' ({$user->email}) telah diperbarui.",
        //     'url'     => url('admin/users'), // Arahkan ke manajemen user
        //     'type'    => 'info',
        //     'image'   => $user->avatar ?? 'profile/user-1.jpg', // Foto user baru tersebut
        // ];

        // // Ambil semua Super Admin
        // $admins = User::role('Super Admin')->get();

        // // Kirim notifikasi
        // Notification::send($admins, new SystemNotification($details));
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
