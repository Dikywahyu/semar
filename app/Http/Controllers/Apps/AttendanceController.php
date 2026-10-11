<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\Apps\ActivitySession;
use App\Models\Apps\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Menampilkan Form Presensi Publik (Scan QR Code).
     */
    public function publicForm($token)
    {
        $session = ActivitySession::with('activity.activityType')
            ->where('qr_code_token', $token)
            ->firstOrFail();

        // Cek apakah sesi sudah ditutup
        if (Carbon::now()->greaterThan($session->end_time)) {
            return view('apps.activities.public_form', compact('session'))
                ->with('error', 'Sesi absensi ini sudah ditutup.');
        }

        return view('apps.activities.public_form', compact('session'));
    }
 
    /**
     * Memproses Input Presensi dari Form Publik / QR Code.
     */
    public function publicSubmit(Request $request, $token)
    {
        $session = ActivitySession::where('qr_code_token', $token)->firstOrFail();
    
        // 1. Validasi Rentang Waktu Absensi (WIB)
        $now       = Carbon::now('Asia/Jakarta');
        $startTime = Carbon::parse($session->start_time)->timezone('Asia/Jakarta');
        $endTime   = Carbon::parse($session->end_time)->timezone('Asia/Jakarta');
    
        // Cek jika presensi BELUM dibuka
        if ($now->lessThan($startTime)) {
            return back()->with('error', 'Gagal! Sesi absensi belum dibuka. Silakan tunggu hingga pukul ' . $startTime->format('H:i') . ' WIB.');
        }
    
        // Cek jika presensi SUDAH ditutup
        if ($now->greaterThan($endTime)) {
            return back()->with('error', 'Gagal! Waktu sesi absensi telah berakhir.');
        }
    
        // 2. Validasi Input Form (Berlaku untuk User Login maupun Tamu)
        $validated = $request->validate([
            'guest_name'         => 'required|string|max:100',
            'guest_email'        => 'required|email|max:100',
            'guest_phone'        => 'required|string|max:30',
            'guest_gender'       => 'required|in:L,P',
            'guest_position'     => 'nullable|string|max:100',
            'guest_organization' => 'nullable|string|max:150',
        ], [
            'guest_name.required'   => 'Nama lengkap wajib diisi.',
            'guest_email.required'  => 'Email wajib diisi.',
            'guest_phone.required'  => 'Nomor telepon/WhatsApp wajib diisi.',
            'guest_gender.required' => 'Jenis kelamin wajib dipilih.',
        ]);
    
        // 3. Pengecekan Duplikasi Presensi & Update Profil User
        if (auth()->check()) {
            $user = auth()->user();
    
            // Cek duplikasi presensi user internal pada sesi ini
            $exists = Attendance::where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->exists();
    
            if ($exists) {
                return back()->with('error', 'Anda sudah melakukan presensi pada sesi ini.');
            }
    
            // --- UPDATE DATA PROFIL USER JIKA ADA PERUBAHAN / DATA KOSONG ---
            $user->update([
                'name'           => $request->guest_name,
                'phone'          => $request->guest_phone,
                'gender'         => $request->guest_gender,
                'position_title' => $request->guest_position,
                'institution'    => $request->guest_organization,
            ]);
    
            $validated['user_id'] = $user->id;
        } else {
            // Cek duplikasi presensi tamu eksternal berdasarkan email
            $existsGuest = Attendance::where('session_id', $session->id)
                ->where('guest_email', $request->guest_email)
                ->exists();
    
            if ($existsGuest) {
                return back()->with('error', 'Email ini sudah digunakan untuk presensi pada sesi ini.');
            }
        }
    
        // 4. Simpan Data Transaksi Presensi
        $validated['session_id']    = $session->id;
        $validated['check_in_time'] = $now;
        $validated['status']        = 'present';
        $validated['method']        = auth()->check() ? 'qr_code' : 'public_form';
    
        Attendance::create($validated);
    
        return back()->with('success', 'Terima kasih, presensi Anda berhasil dicatat dan profil diperbarui!');
    }

    /**
     * Menampilkan Halaman Rekap & Data Presensi (DataTables AJAX).
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $attendances = Attendance::with(['session.activity', 'user'])
                ->latest()
                ->get();

            // Hitung Rekap Gender (L/P)
            $totalMale = $attendances->filter(function ($item) {
                return ($item->user && $item->user->gender === 'L') || $item->guest_gender === 'L';
            })->count();

            $totalFemale = $attendances->filter(function ($item) {
                return ($item->user && $item->user->gender === 'P') || $item->guest_gender === 'P';
            })->count();

            return response()->json([
                'data' => $attendances,
                'summary' => [
                    'total_male'   => $totalMale,
                    'total_female' => $totalFemale,
                    'total'        => $attendances->count(),
                ]
            ]);
        }

        return view('apps.attendances.index');
    }

    /**
     * Menghapus record presensi.
     */
    public function destroy($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Data presensi berhasil dihapus.'
        ]);
    }
}