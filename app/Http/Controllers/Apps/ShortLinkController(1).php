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
use Intervention\Image\Drivers\Gd\Driver; // Jika error, ganti ke 'Imagick'

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
            $options = new QROptions([
                'version'    => -1,  // Ubah dari 5 ke -1
                'eccLevel'   => 'H',
                'outputType' => 'svg',
            ]);

            $svgRaw = (new QRCode($options))->render($url);

            if (strpos($svgRaw, 'data:image/svg+xml;base64,') === 0) {
                $svgRaw = base64_decode(substr($svgRaw, 26));
            }

            $logoUrl = '../../assets/UNITRI.png';
            $logoContent = @file_get_contents($logoUrl);

            if ($logoContent) {
                $logoBase64 = 'data:image/png;base64,' . base64_encode($logoContent);
                $logoElement = sprintf(
                    '<image x="%d" y="%d" width="%d" height="%d" href="%s" />',
                    8,
                    8,
                    30,
                    30,
                    $logoBase64
                );
                $svgRaw = str_replace('</svg>', $logoElement . '</svg>', $svgRaw);
            }

            $finalData = 'data:image/svg+xml;base64,' . base64_encode($svgRaw);

            return response()->json(['data' => $finalData]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function generateInstantQr(Request $request)
    {
        $request->validate(['url' => 'required|url']);

        try {
            // Gunakan nilai string / integer sederhana agar kompatibel di semua versi library
            $options = new QROptions([
                'version'     => -1,     // -1 = Otomatis menyesuaikan panjang URL
                'maxVersion'  => 40,     // Versi maksimal
                'eccLevel'    => 'H',    // String 'H' (High error correction)
                'outputType'  => 'svg',  // String 'svg'
            ]);

            // 1. Generate QR Code
            $svgRaw = (new QRCode($options))->render($request->url);

            // 2. Decode kembali ke raw HTML <svg> jika berformat base64
            if (strpos($svgRaw, 'data:image/svg+xml;base64,') === 0) {
                $svgRaw = base64_decode(substr($svgRaw, 26));
            }

            // 3. Ambil Logo dari URL
            $logoUrl = '../../assets/UNITRI.png';
            $logoContent = @file_get_contents($logoUrl);

            if ($logoContent) {
                $logoBase64 = 'data:image/png;base64,' . base64_encode($logoContent);

                // Sisipkan logo ke dalam elemen SVG
                $logoElement = sprintf(
                    '<image x="%d" y="%d" width="%d" height="%d" href="%s" />',
                    8,
                    8,
                    30,
                    30,
                    $logoBase64
                );

                $svgRaw = str_replace('</svg>', $logoElement . '</svg>', $svgRaw);
            }

            // 4. Encode kembali ke Base64 untuk dikirim ke frontend
            $finalData = 'data:image/svg+xml;base64,' . base64_encode($svgRaw);

            return response()->json(['data' => $finalData]);
        } catch (\Exception $e) {
            // Tangkap error dan kirimkan pesan error asli
            return response()->json(['message' => $e->getMessage()], 500);
        }
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
