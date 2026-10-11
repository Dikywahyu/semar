<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\Apps\ActivityReport;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ActivityReportController extends Controller
{
    
    /**
     * Menampilkan Halaman Pengesahan Publik untuk Pimpinan via Token
     */
    public function publicApprovalView($token)
    {
        $report = ActivityReport::where('digital_signature_token', $token)
            ->with(['activity.sessions.attendances', 'reporter'])
            ->firstOrFail();
    
        return view('apps.activity_reports.public_approval', compact('report'));
    }
    
    /**
     * Memproses Pengesahan Publik oleh Pimpinan
     */
    public function publicApprovalSubmit(Request $request, $token)
    {
        $report = ActivityReport::where('digital_signature_token', $token)->firstOrFail();
    
        $report->update([
            'approval_status' => 'approved',
            'approved_at'      => \Carbon\Carbon::now('Asia/Jakarta'),
        ]);
    
        return back()->with('success', 'Berita Acara Kegiatan berhasil disahkan!');
    }

    /**
     * Fetch Laporan berdasarkan Activity ID (AJAX)
     */
    public function getByActivity($activityId)
    {
        $report = ActivityReport::where('activity_id', $activityId)->first();

        return response()->json([
            'status' => 'success',
            'data'   => $report
        ]);
    }

    /**
     * Menyimpan (Store) ATAU Memperbarui (Update) Berita Acara Notulensi.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'activity_id'            => 'required|exists:activities,id',
            'document_number'        => 'nullable|string|max:100',
            'summary'                => 'nullable|string',
            'decisions'              => 'required|string',
            'action_items'           => 'nullable|string',
            'attachment_file'        => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:5048',
            'approved_by_name'       => 'nullable|string|max:150',
            'approved_by_position'   => 'nullable|string|max:150',
            'approved_by_identifier' => 'nullable|string|max:100',
        ]);
    
        $report = ActivityReport::where('activity_id', $request->activity_id)->first();
    
        // Handle File Lampiran
        if ($request->hasFile('attachment_file')) {
            if ($report && $report->attachment_file) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($report->attachment_file);
            }
            $validated['attachment_file'] = $request->file('attachment_file')->store('activity_reports', 'public');
        }
    
        $validated['created_by']      = auth()->id();
        $validated['approval_status'] = $report ? $report->approval_status : 'draft';
        
        // GENERATE TOKEN UNIK UNTUK LINK PENGESAHAN JIKA BELUM ADA
        if (!$report || !$report->digital_signature_token) {
            $validated['digital_signature_token'] = Str::random(40);
        }
    
        $savedReport = ActivityReport::updateOrCreate(
            ['activity_id' => $request->activity_id],
            $validated
        );
    
        return response()->json([
            'status'  => 'success',
            'message' => 'Berita Acara berhasil disimpan!',
            'data'    => $savedReport
        ]);
    }

    /**
     * Pengesahan / Digital Approval Berita Acara.
     */
    public function approve($id)
    {
        $report = ActivityReport::findOrFail($id);
        
        $report->update([
            'approval_status'         => 'approved',
            'digital_signature_token' => Str::random(40),
            'approved_at'             => Carbon::now('Asia/Jakarta'),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Berita Acara berhasil disahkan secara digital!'
        ]);
    }
}