<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Apps\ShortLink;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

// Library untuk QR
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Library untuk Image v4
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ShortLinkController extends Controller
{
    /**
     * Tampilkan daftar link
     */
    public function index()
    {
        $links = ShortLink::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();
        return view('apps.short_links', compact('links'));
    }

    /**
     * AJAX Endpoint untuk ambil QR Base64
     */
    public function getQrData($id)
    {
        $link = ShortLink::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $url = url('/s/' . $link->short_code);

        try {
            $finalData = $this->generateQrSvgWithLogo($url);
            return response()->json(['data' => $finalData]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal generate QR Code: ' . $e->getMessage()], 500);
        }
    }

    /**
     * AJAX Endpoint untuk Instant QR Generator
     */
    public function generateInstantQr(Request $request)
    {
        $request->validate(['url' => 'required|url']);

        try {
            $finalData = $this->generateQrSvgWithLogo($request->url);
            return response()->json(['data' => $finalData]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal generate QR Code: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Private Helper: Generate SVG QR Code dengan Logo di Tengah secara Dinamis
     */
    private function generateQrSvgWithLogo($url)
    {
        $options = new QROptions([
            'version'    => -1,
            'maxVersion' => 40,
            'eccLevel'   => 'H',
            'outputType' => 'svg',
        ]);

        // 1. Generate QR Code
        $svgRaw = (new QRCode($options))->render($url);

        // 2. Decode jika berformat Base64
        if (strpos($svgRaw, 'data:image/svg+xml;base64,') === 0) {
            $svgRaw = base64_decode(substr($svgRaw, 26));
        }

        // 3. Ambil Logo
        $logoUrl = '../../assets/UNITRI.png';
        $logoContent = @file_get_contents($logoUrl);

        if ($logoContent) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoContent);

            // A. Ambil nilai viewBox SVG (contoh: viewBox="0 0 85 85")
            preg_match('/viewBox="([^"]+)"/', $svgRaw, $matches);

            $svgWidth = 45;
            if (isset($matches[1])) {
                $viewBoxValues = explode(' ', $matches[1]);
                if (count($viewBoxValues) === 4) {
                    $svgWidth = (float)$viewBoxValues[2];
                }
            }

            // B. KUNCI PERBAIKAN: Gunakan persentase ideal (20% - 22% dari total QR)
            // Angka 0.20 - 0.22 adalah rasio paling pas agar logo tidak menutupi modul QR terlalu banyak
            // sehingga aman di-scan dan ukurannya selalu tampak seimbang.
            $logoSize = $svgWidth * 0.51;

            // C. Hitung Koordinat Tengah Presisi
            $x = ($svgWidth - $logoSize) / 2;
            $y = ($svgWidth - $logoSize) / 2;

            // D. Sisipkan Logo
            $logoElement = sprintf(
                '<image x="%.2f" y="%.2f" width="%.2f" height="%.2f" href="%s" />',
                $x,
                $y,
                $logoSize,
                $logoSize,
                $logoBase64
            );

            $svgRaw = str_replace('</svg>', $logoElement . '</svg>', $svgRaw);
        }

        return 'data:image/svg+xml;base64,' . base64_encode($svgRaw);
    }

    /**
     * Proses Tambah Link
     */
    public function store(Request $request)
    {
        $request->validate([
            'destination_url' => 'required|url',
            'short_code' => 'nullable|alpha_dash|max:50'
        ]);

        $code = $request->short_code ?: Str::random(6);

        if (ShortLink::where('short_code', $code)->exists()) {
            return back()->withInput()->with('error_msg', 'Kode pendek sudah digunakan.');
        }

        ShortLink::create([
            'user_id' => Auth::id(),
            'short_code' => $code,
            'destination_url' => $request->destination_url
        ]);

        return redirect()->route('short-links.index')->with('success_msg', 'Short Link berhasil dibuat!');
    }

    /**
     * Proses Update
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'destination_url' => 'required|url',
            'short_code' => 'required|alpha_dash|max:50'
        ]);

        $link = ShortLink::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        if ($link->short_code !== $request->short_code && ShortLink::where('short_code', $request->short_code)->exists()) {
            return back()->with('error_msg', 'Kode pendek baru sudah digunakan.');
        }

        $link->update([
            'short_code' => $request->short_code,
            'destination_url' => $request->destination_url
        ]);

        return redirect()->route('short-links.index')->with('success_msg', 'Short Link berhasil diperbarui!');
    }

    /**
     * Proses Hapus
     */
    public function destroy($id)
    {
        $link = ShortLink::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $link->delete();

        return redirect()->route('short-links.index')->with('success_msg', 'Short Link berhasil dihapus.');
    }

    /**
     * Redirect URL
     */
    public function handleRedirect($code)
    {
        $link = ShortLink::where('short_code', $code)->firstOrFail();
        $link->increment('clicks_count');
        return redirect()->away($link->destination_url);
    }
}
