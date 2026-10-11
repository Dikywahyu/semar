<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\Apps\ActivityType;
use Illuminate\Http\Request;

class ActivityTypeController extends Controller
{
    /**
     * Menampilkan halaman utama atau data JSON untuk DataTables.
     */
    public function index(Request $request)
    {
        // Jika request datang dari DataTables AJAX
        if ($request->ajax()) {
            $data = ActivityType::latest()->get();

            return response()->json([
                'data' => $data
            ]);
        }

        // Jika request HTTP biasa, tampilkan view blade
        return view('apps.activity_types.index');
    }

    /**
     * Menyimpan tipe kegiatan baru via AJAX.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:activity_types,name',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Nama tipe kegiatan wajib diisi.',
            'name.unique'   => 'Nama tipe kegiatan sudah ada.',
        ]);

        ActivityType::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Tipe kegiatan berhasil ditambahkan.'
        ], 201);
    }

    /**
     * Mengambil detail data tipe kegiatan untuk modal Edit via AJAX.
     */
    public function show($id)
    {
        $activityType = ActivityType::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $activityType
        ]);
    }

    /**
     * Memperbarui data tipe kegiatan via AJAX.
     */
    public function update(Request $request, $id)
    {
        $activityType = ActivityType::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:activity_types,name,' . $id,
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Nama tipe kegiatan wajib diisi.',
            'name.unique'   => 'Nama tipe kegiatan sudah digunakan.',
        ]);

        $activityType->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Tipe kegiatan berhasil diperbarui.'
        ]);
    }

    /**
     * Menghapus tipe kegiatan via AJAX.
     */
    public function destroy($id)
    {
        $activityType = ActivityType::findOrFail($id);

        // Opsional: Cek apakah tipe kegiatan ini sedang digunakan di tabel activities
        if ($activityType->activities()->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tipe kegiatan tidak dapat dihapus karena masih digunakan oleh beberapa kegiatan.'
            ], 422);
        }

        $activityType->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Tipe kegiatan berhasil dihapus.'
        ]);
    }
}