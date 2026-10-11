<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\Apps\Activity;
use App\Models\Apps\ActivityType;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Menampilkan daftar kegiatan (AJAX DataTables / Blade View).
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $user = auth()->user();
    
            $query = Activity::with(['activityType'])
                ->withCount('sessions');
    
            // Jika BUKAN Super Admin, filter hanya data yang dibuat oleh user tsb
            if (!$user->hasRole('Super Admin')) {
                $query->where('created_by', $user->id); // Sesuaikan 'created_by' dengan nama kolom ID pembuat di tabel activities Anda (misal: user_id)
            }
    
            $activities = $query->latest()->get();
    
            return response()->json(['data' => $activities]);
        }
    
        $activityTypes = ActivityType::all();
    
        return view('apps.activities.index', compact('activityTypes'));
    }

    /**
     * Menyimpan kegiatan baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'activity_type_id' => 'required|exists:activity_types,id',
            'location'         => 'nullable|string|max:255',
            'description'      => 'nullable|string',
        ], [
            'title.required'            => 'Judul kegiatan wajib diisi.',
            'activity_type_id.required' => 'Tipe kegiatan wajib dipilih.',
            'activity_type_id.exists'   => 'Tipe kegiatan tidak valid.',
        ]);

        $validated['created_by'] = auth()->id() ?? 1; // ID User login

        Activity::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Kegiatan baru berhasil dibuat.'
        ], 201);
    }

    /**
     * Menampilkan detail kegiatan beserta sesi untuk Modal Edit / Sesi.
     */
    public function show($id)
    {
        $activity = Activity::with([
            'activityType', 
            'sessions.attendances.user', 
            'report'
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $activity
        ]);
    }

    /**
     * Memperbarui data kegiatan.
     */
    public function update(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'activity_type_id' => 'required|exists:activity_types,id',
            'location'         => 'nullable|string|max:255',
            'description'      => 'nullable|string',
        ]);

        $activity->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data kegiatan berhasil diperbarui.'
        ]);
    }

    /**
     * Menghapus kegiatan.
     */
    public function destroy($id)
    {
        $activity = Activity::findOrFail($id);
        $activity->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Kegiatan dan seluruh sesi di dalamnya berhasil dihapus.'
        ]);
    }
}