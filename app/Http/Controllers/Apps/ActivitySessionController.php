<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\Apps\Activity;
use App\Models\Apps\ActivitySession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivitySessionController extends Controller
{
    /**
     * Menyimpan sesi absensi baru di dalam suatu kegiatan.
     */
    public function store(Request $request, $activityId)
    {
        $activity = Activity::findOrFail($activityId);

        $validated = $request->validate([
            'session_name' => 'required|string|max:100',
            'start_time'   => 'required|date',
            'end_time'     => 'required|date|after:start_time',
            'access_type'  => 'required|in:public,restricted',
        ], [
            'session_name.required' => 'Nama sesi wajib diisi.',
            'start_time.required'   => 'Waktu mulai absensi wajib diisi.',
            'end_time.required'     => 'Waktu tutup absensi wajib diisi.',
            'end_time.after'        => 'Waktu tutup harus lebih lambat dari waktu mulai.',
        ]);

        $validated['activity_id']   = $activity->id;
        // Generate Token QR Unik acak 32 karakter
        $validated['qr_code_token'] = Str::random(32);

        ActivitySession::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Sesi absensi berhasil dibuat.'
        ], 201);
    }

    /**
     * Menampilkan detail sesi spesifik.
     */
    public function show($id)
    {
        $activity = Activity::with(['sessions.attendances.user', 'activityType'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $session
        ]);
    }

    /**
     * Memperbarui sesi absensi.
     */
    public function update(Request $request, $id)
    {
        $session = ActivitySession::findOrFail($id);

        $validated = $request->validate([
            'session_name' => 'required|string|max:100',
            'start_time'   => 'required|date',
            'end_time'     => 'required|date|after:start_time',
            'access_type'  => 'required|in:public,restricted',
        ], [
            'session_name.required' => 'Nama sesi wajib diisi.',
            'start_time.required'   => 'Waktu mulai absensi wajib diisi.',
            'end_time.required'     => 'Waktu tutup absensi wajib diisi.',
            'end_time.after'        => 'Waktu tutup harus lebih lambat dari waktu mulai.',
        ]);

        $session->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Sesi absensi berhasil diperbarui.'
        ]);
    }

    /**
     * Menghapus sesi absensi.
     */
    public function destroy($id)
    {
        $session = ActivitySession::findOrFail($id);
        
        // Hapus presensi terkait jika ada (keamanan integritas data)
        if (method_exists($session, 'attendances')) {
            $session->attendances()->delete();
        }

        $session->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Sesi absensi berhasil dihapus.'
        ]);
    }

    /**
     * Menampilkan Layar Tampilan QR Code Sesi untuk Proyektor/Layar.
     */
    public function qrDisplay($id)
    {
        $session = ActivitySession::with('activity.activityType')->findOrFail($id);

        // URL Publik yang akan di-encode ke QR Code
        $qrUrl = route('attendances.public_form', $session->qr_code_token);

        return view('apps.activities.qr_display', compact('session', 'qrUrl'));
    }
}