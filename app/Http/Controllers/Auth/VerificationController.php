<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller; // Pastikan ini ada
use Illuminate\Foundation\Auth\VerifiesEmails;

class VerificationController extends Controller
{
    use VerifiesEmails;

    protected $redirectTo = '/dashboard';


    // Kita pindahkan middleware ke file Route
}
