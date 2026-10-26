<?php

namespace App\Http\Controllers\Capstone;

use App\Http\Controllers\Controller;
use App\Models\Capstone\CapstoneGroup;
use App\Models\Capstone\CapstoneExpertise;
use App\Models\Dosen\Dosen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CapstoneCoordinatorController extends Controller
{

    // Index halaman koordinator (mengarah ke view & mendukung AJAX)
    public function index(Request $request)
    {
        $user = Auth::user();
        $dosenId = $user?->id_dosen ?? null;

        // Ambil keahlian yang dikoordinatori oleh dosen yang sedang login
        $expertises = CapstoneExpertise::where('coordinator_id', $dosenId)->get();

        $query = CapstoneGroup::with(['leader', 'members.student', 'supervisor', 'examiner', 'expertise.dosens']);

        // Jika bukan Super Admin / role global, filter berdasarkan keahlian koordinator
        if ($dosenId && !($user && method_exists($user, 'hasRole') && $user->hasRole(['Super Admin', 'BAAK', 'Akademik', 'KPS']))) {
            $query->whereHas('expertise', function ($q) use ($dosenId) {
                $q->where('coordinator_id', $dosenId);
            });
        }

        if ($request->filled('expertise_id')) {
            $query->where('expertise_id', $request->expertise_id);
        }

        $groups = $query->get();

        // ==========================================
        // DATA UNTUK GRAFIK (CHART BEBAN BIMBINGAN)
        // ==========================================
        $dosenList = [];
        $pembimbingStats = [];
        $pengujiStats = [];

        // Kumpulkan semua nama dosen unik terlebih dahulu
        foreach ($groups as $group) {
            if ($group->supervisor && $group->supervisor->nama_dosen) {
                $dosenList[$group->supervisor->nama_dosen] = true;
            }
            if ($group->examiner && $group->examiner->nama_dosen) {
                $dosenList[$group->examiner->nama_dosen] = true;
            }
        }

        $chartLabels = array_keys($dosenList);

        // Hitung beban untuk masing-masing dosen
        foreach ($chartLabels as $namaDosen) {
            $bebanPembimbing = 0; // <-- Pastikan tanpa spasi setelah tanda $
            $bebanPenguji = 0;

            foreach ($groups as $group) {
                $jumlahAnggota = $group->members->where('status', 'accepted')->count();
                $totalMhs = 1 + $jumlahAnggota;

                // Cek sebagai pembimbing
                if ($group->supervisor && ($group->supervisor->nama_dosen ?? '') === $namaDosen) {
                    $bebanPembimbing += $totalMhs;
                }

                // Cek sebagai penguji
                if ($group->examiner && ($group->examiner->nama_dosen ?? '') === $namaDosen) {
                    $bebanPenguji += $totalMhs;
                }
            }

            $pembimbingStats[] = $bebanPembimbing;
            $pengujiStats[] = $bebanPenguji;
        }
        // ==========================================

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $groups,
                'coordinator_expertises' => $expertises
            ]);
        }

        // Jangan lupa kirim $chartLabels dan $chartData ke view
        return view('capstone.koordinator.index', compact('groups', 'expertises', 'chartLabels', 'pembimbingStats', 'pengujiStats'));
    }

    // Endpoint alternatif untuk mengambil data kelompok via AJAX
    public function getGroupsByExpertise(Request $request)
    {
        return $this->index($request);
    }

    // Plotting Dosen Pembimbing & Dosen Penguji
    public function plotLecturers(Request $request, $groupId)
    {
        $request->validate([
            'supervisor_id' => 'nullable|exists:dosens,id_dosen',
            'examiner_id' => 'nullable|exists:dosens,id_dosen',
        ]);

        $group = CapstoneGroup::findOrFail($groupId);
        $group->update([
            'supervisor_id' => $request->supervisor_id,
            'examiner_id' => $request->examiner_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Plotting dosen pembimbing dan penguji berhasil disimpan.'
        ]);
    }

    // Menu KPI: Setujui / Belum untuk Ujian
    public function updateKpiApproval(Request $request, $groupId)
    {
        $request->validate(['kpi_approval' => 'required|in:pending,approved,rejected']);

        $group = CapstoneGroup::findOrFail($groupId);
        $group->update(['kpi_approval' => $request->kpi_approval]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status KPI ujian berhasil diperbarui.'
        ]);
    }

    // Toggle ACC Koordinator & Evaluasi Status Kelayakan Final Selesai
    public function toggleAccCoordinator($groupId)
    {
        $group = CapstoneGroup::findOrFail($groupId);
        $group->update(['acc_coordinator' => !$group->acc_coordinator]);

        // Cek status final kelayakan selesai jika semua pihak telah ACC
        if ($group->acc_coordinator && $group->acc_supervisor && $group->acc_kps) {
            $group->update(['final_status' => 'selesai']);
        } else {
            $group->update(['final_status' => 'proses']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status ACC Koordinator berhasil diubah.',
            'acc_coordinator' => $group->acc_coordinator
        ]);
    }
}
