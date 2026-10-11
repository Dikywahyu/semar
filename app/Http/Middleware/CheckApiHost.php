<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiClient;

class CheckApiHost
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Ambil Client ID dari Header
        $clientId = $request->header('X-Client-ID');

        // 2. Dapatkan Host/Domain pengirim request
        $requestHost = $request->getHost();

        // 3. Cari di database ApiClient
        $client = ApiClient::where('client_id', $clientId)
            ->where('is_active', true)
            ->first();

        // 4. Validasi Keberadaan Client
        if (!$client) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized: Invalid Client ID or Client is inactive.'
            ], 401);
        }

        // 5. Validasi Host (Kecuali jika diset '*' untuk akses publik)
        if ($client->allowed_host !== '*' && $client->allowed_host !== $requestHost) {
            return response()->json([
                'status'  => 'error',
                'message' => "Unauthorized: Host {$requestHost} is not allowed to access this resource."
            ], 401);
        }

        return $next($request);
    }
}
