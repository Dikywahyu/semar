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
        $isGlobalAdmin = $user?->hasAnyRole(['Super Admin', 'KPS', 'Kaprodi', 'Admin', 'BAAK', 'Akademik']);
        abort_unless($isGlobalAdmin || $dosenId, 403);

        // Ambil keahlian yang dikoordinatori oleh dosen yang sedang login
        $expertises = CapstoneExpertise::where('coordinator_id', $dosenId)->get();

        $query = CapstoneGroup::with(['leader', 'members.student', 'supervisor', 'examiner', 'expertise.dosens', 'kpiApprover', 'coordinatorApprover']);

        // Jika bukan Super Admin / role global, filter berdasarkan keahlian koordinator
        if (! $isGlobalAdmin) {
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
        $validated = $request->validate([
            'supervisor_id' => 'nullable|exists:dosens,id_dosen',
            'examiner_id' => 'nullable|exists:dosens,id_dosen',
            'project_location' => 'nullable|string|max:255',
        ]);

        $group = CapstoneGroup::findOrFail($groupId);
        $this->authorizeGroupPlotting($group);
        $supervisorChanged = $group->supervisor_id !== ($validated['supervisor_id'] ?? null);
        $group->update([
            'supervisor_id' => $validated['supervisor_id'] ?? null,
            'examiner_id' => $validated['examiner_id'] ?? null,
            'project_location' => $validated['project_location'] ?? null,
        ]);
        if ($supervisorChanged) {
            $group->update([
                'acc_supervisor' => false,
                'acc_kps' => false,
                'final_status' => 'proses',
                'exam_invitation_sent_at' => null,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Plotting dosen pembimbing dan penguji berhasil disimpan.'
        ]);
    }

    // Menu KPI: Setujui / Belum untuk Ujian
    public function updateKpiApproval(Request $request, $groupId)
    {
        $validated = $request->validate(['kpi_approval' => 'required|in:pending,approved,rejected']);
        $group = CapstoneGroup::findOrFail($groupId);
        $this->authorizeGroupCoordinator($group);
        abort_if($validated['kpi_approval'] === 'approved' && (! $group->draft_report_file || ! $group->consultation_card_file), 409, 'Kartu konsultasi dan draft laporan Capstone harus diunggah sebelum KPI disetujui untuk ujian.');
        $approved = $validated['kpi_approval'] === 'approved';
        $isSuperAdmin = Auth::user()->hasRole('Super Admin');
        $role = $isSuperAdmin ? 'Koordinator CD (diwakilkan Super Admin)' : 'Koordinator CD';
        $changes = [
            'kpi_approval' => $validated['kpi_approval'],
        ];
        if ($approved) {
            $changes += [
                'kpi_approved_by_role' => $role,
                'kpi_approved_by_user_id' => Auth::id(),
                'kpi_approved_at' => now(),
                'approved_by_role' => $role,
                'approved_by_user_id' => Auth::id(),
            ];
        }
        $group->update($changes);

        return response()->json([
            'status' => 'success',
            'message' => 'Status KPI ujian berhasil diperbarui.'
        ]);
    }

    // Toggle ACC Koordinator & Evaluasi Status Kelayakan Final Selesai
    public function toggleAccCoordinator(Request $request, $groupId)
    {
        $group = CapstoneGroup::findOrFail($groupId);
        $this->authorizeGroupCoordinator($group);
        $approved = ! $group->acc_coordinator;
        abort_if($approved && ! $group->final_report_file, 409, 'Laporan final harus diunggah sebelum ACC kelulusan koordinator.');
        $role = Auth::user()->hasRole('Super Admin') ? 'Koordinator CD (diwakilkan Super Admin)' : 'Koordinator CD';
        $changes = ['acc_coordinator' => $approved];
        if ($approved) {
            $changes += [
                'coordinator_approved_by_role' => $role,
                'coordinator_approved_by_user_id' => Auth::id(),
                'coordinator_approved_at' => now(),
                'approved_by_role' => $role,
                'approved_by_user_id' => Auth::id(),
            ];
        }
        $group->update($changes);
        $this->refreshFinalStatus($group);

        return response()->json([
            'status' => 'success',
            'message' => 'Status ACC Koordinator berhasil diubah.',
            'acc_coordinator' => $group->acc_coordinator
        ]);
    }

    private function authorizeGroupCoordinator(CapstoneGroup $group): void
    {
        $user = Auth::user();
        if ($user?->hasRole('Super Admin')) {
            return;
        }

        $expertise = $group->expertise()->first();
        $dosenId = $user?->id_dosen;
        abort_unless(
            $dosenId && $expertise && (
                $expertise->coordinator_id === $dosenId
                || $user?->hasAnyRole(['Koordinator CD', 'Koordinator Capstone', 'Koordinator'])
                    && $expertise->lecturers()->where('dosens.id_dosen', $dosenId)->exists()
            ),
            403
        );
    }

    private function authorizeGroupPlotting(CapstoneGroup $group): void
    {
        if (Auth::user()?->hasAnyRole(['Super Admin', 'KPS', 'Kaprodi'])) {
            return;
        }

        $this->authorizeGroupCoordinator($group);
    }

    private function refreshFinalStatus(CapstoneGroup $group): void
    {
        $group->refresh();
        $group->update([
            'final_status' => $group->acc_coordinator && $group->acc_supervisor && $group->acc_kps && $group->final_report_file
                ? 'selesai'
                : 'proses',
        ]);
    }
}
