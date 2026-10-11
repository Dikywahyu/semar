<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Activitylog\Models\Activity;

class ApiClientController extends Controller
{
    /**
     * Tampilan utama daftar API Client
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Mengambil data terbaru
            $clients = ApiClient::latest()->get();

            // Memetakan data agar sesuai dengan kebutuhan kolom di DataTable
            $data = $clients->map(function ($client) {
                return [
                    'hashid'        => $client->hashid,
                    'icon'          => '<i class="' . $client->icon . ' fs-7 text-primary"></i>',
                    'app_name'      => $client->app_name,
                    'description'   => $client->description,
                    'client_id'     => $client->client_id,
                    'client_secret' => $client->client_secret,
                    'allowed_host'  => $client->allowed_host,
                    'is_active'     => $client->is_active
                        ? '<span class="badge bg-success-subtle text-success">Aktif</span>'
                        : '<span class="badge bg-danger-subtle text-danger">Nonaktif</span>',
                    'action'        => $this->renderActionButtons($client)
                ];
            });

            return response()->json(['data' => $data]);
        }

        return view('admin.api_clients.index');
    }

    /**
     * Helper untuk merender tombol aksi (server-side)
     */
    private function renderActionButtons($client)
    {
        $hashid = $client->hashid;
        return '
        <div class="dropdown dropstart">
            <a href="javascript:void(0)" class="text-muted" data-bs-toggle="dropdown">
                <i class="ti ti-dots-vertical fs-6"></i>
            </a>
            <ul class="dropdown-menu">
                <li>
                    <button class="dropdown-item d-flex align-items-center gap-3 btn-edit-client" data-id="' . $hashid . '">
                        <i class="fs-4 ti ti-edit"></i> Edit Client
                    </button>
                </li>
                <li>
                    <button class="dropdown-item d-flex align-items-center gap-3 btn-toggle-status" data-id="' . $hashid . '">
                        <i class="fs-4 ti ti-power"></i> ' . ($client->is_active ? 'Nonaktifkan' : 'Aktifkan') . '
                    </button>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <button class="dropdown-item d-flex align-items-center gap-3 text-danger btn-delete-client" data-id="' . $hashid . '">
                        <i class="fs-4 ti ti-trash"></i> Hapus
                    </button>
                </li>
            </ul>
        </div>';
    }

    /**
     * Menyimpan Client Baru dengan Auto-Generate Credentials
     */
    public function store(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'allowed_host' => 'required',
        ]);

        $client = ApiClient::create([
            'icon'          => $request->icon ?? 'ti ti-apps',
            'app_name'      => $request->app_name,
            'description'   => $request->description,
            'client_id'     => 'cli_' . Str::random(16),
            'client_secret' => Str::random(32),
            'allowed_host'  => $request->allowed_host,
            'is_active'     => true,
        ]);

        // LOG AKTIVITAS
        activity()
            ->performedOn($client)
            ->causedBy(auth()->user())
            ->log("Menambah API Client Baru: {$client->app_name}");

        return response()->json(['message' => 'API Client berhasil ditambahkan']);
    }

    /**
     * Mengambil detail client menggunakan Hashid
     */
    public function show($hashid)
    {
        $client = ApiClient::findByHashid($hashid);
        return response()->json($client);
    }

    public function update(Request $request, $hashid)
    {
        $client = ApiClient::findByHashid($hashid);

        $request->validate([
            'app_name' => 'required|string|max:255',
            'allowed_host' => 'required',
        ]);

        $client->update([
            'icon' => $request->icon,
            'app_name' => $request->app_name,
            'description' => $request->description,
            'allowed_host' => $request->allowed_host,
        ]);

        // LOG AKTIVITAS
        activity()
            ->performedOn($client)
            ->causedBy(auth()->user())
            ->log("Mengupdate data API Client: {$client->app_name} (Host: {$client->allowed_host})");

        return response()->json(['message' => 'Data client berhasil diperbarui']);
    }

    /**
     * Update status aktif/nonaktif
     */
    public function toggleStatus($hashid)
    {
        $client = ApiClient::findByHashid($hashid);
        $client->update(['is_active' => !$client->is_active]);

        $status = $client->is_active ? 'MENGAKTIFKAN' : 'MENONAKTIFKAN';

        // LOG AKTIVITAS
        activity()
            ->performedOn($client)
            ->causedBy(auth()->user())
            ->log("{$status} akses API untuk: {$client->app_name}");

        return response()->json([
            'message' => 'Status ' . $client->app_name . ' berhasil diperbarui',
            'is_active' => $client->is_active
        ]);
    }

    /**
     * Menghapus Client
     */
    public function destroy($hashid)
    {
        $client = ApiClient::findByHashid($hashid);
        $name = $client->app_name;

        // LOG AKTIVITAS (Lakukan sebelum delete agar data model masih terbaca)
        activity()
            ->causedBy(auth()->user())
            ->log("MENGHAPUS API Client: {$name}");

        $client->delete();

        return response()->json(['message' => 'Client telah dihapus']);
    }
}
