<?php

namespace App\Http\Responses;

use Laravel\Passport\Contracts\AuthorizationViewResponse;

class SSOAuthorizeResponse implements AuthorizationViewResponse
{
    /**
     * Menampung parameter bawaan dari core Passport
     */
    protected array $parameters = [];

    /**
     * SINKRONISASI: Menambahkan nilai default [] agar 100% cocok dengan kontrak Passport
     */
    public function withParameters(array $parameters = []): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Berfungsi merelasikan data view OAuth Passport secara aman.
     */
    public function toResponse($request)
    {
        // Gabungkan parameter default dari request dengan parameter kustom Passport jika ada
        $viewData = array_merge(
            $request->attributes->get('oauth_authorize_params') ?? [],
            $this->parameters
        );

        // Kembalikan langsung ke view default bawaan Passport
        return response()->view('passport.authorize', $viewData);
    }
}