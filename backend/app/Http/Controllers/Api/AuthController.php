<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\PendampingLoginRequest;
use App\Models\AccessToken;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('username', $request->string('username'))
            ->whereIn('role', ['admin', 'guru'])
            ->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            return response()->json(['message' => 'Username atau password tidak valid.'], 401);
        }

        return response()->json([
            'token' => $user->createToken('staff-session')->plainTextToken,
            'user' => $user->only(['id', 'name', 'username', 'role']),
        ]);
    }

    public function loginPendamping(PendampingLoginRequest $request)
    {
        $accessToken = AccessToken::with('student')
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->get()
            ->first(fn (AccessToken $item) => Hash::check($request->string('token'), $item->token));

        if (! $accessToken || ! $accessToken->student) {
            return response()->json(['message' => 'Token tidak valid atau sudah kedaluwarsa.'], 401);
        }

        /** @var Student $student */
        $student = $accessToken->student;
        return response()->json([
            'token' => $student->createToken('pendamping-session')->plainTextToken,
            'student' => $student->load('currentLevel')->only(['id', 'nama', 'current_level_id', 'currentLevel']),
        ]);
    }

    public function logout(\Illuminate\Http\Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();
        return response()->json(['message' => 'Logout berhasil.']);
    }
}
