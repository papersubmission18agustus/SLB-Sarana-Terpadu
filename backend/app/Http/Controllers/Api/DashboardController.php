<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccessToken;
use App\Models\Student;
use App\Models\User;

class DashboardController extends Controller
{
    public function show()
    {
        return response()->json([
            'data' => [
                'total_pengguna' => User::count() + Student::count(),
                'pendamping' => Student::count(),
                'guru' => User::where('role', 'guru')->count(),
                'admin' => User::where('role', 'admin')->count(),
                'token_aktif' => AccessToken::where('is_active', true)
                    ->where('expires_at', '>', now())
                    ->count(),
            ],
        ]);
    }
}
