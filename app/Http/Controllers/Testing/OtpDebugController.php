<?php

declare(strict_types=1);

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * A back door for Playwright, which has no password to log in with: it reads
 * the plaintext code that DispatchLoginOtp / DispatchEmailChangeOtp cache
 * (local/testing only) instead of parsing a mailbox. Registered only in
 * local/testing (routes/web.php) and re-checked here as defense in depth —
 * this must never be reachable in production.
 */
class OtpDebugController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $email = strtolower(trim((string) $request->query('email')));

        return response()->json([
            'code' => Cache::get("login-otp-debug:{$email}"),
        ]);
    }

    public function emailChange(Request $request): JsonResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $email = strtolower(trim((string) $request->query('email')));

        return response()->json([
            'code' => Cache::get("email-change-otp-debug:{$email}"),
        ]);
    }
}
