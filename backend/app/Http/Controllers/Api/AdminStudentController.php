<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Models\AccessToken;
use App\Models\Student;
use App\Notifications\AccessTokenNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminStudentController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Student::with('currentLevel')->withCount('accessTokens')->latest()->paginate(20)]);
    }

    public function store(StoreStudentRequest $request)
    {
        $student = Student::create($request->validated());
        return response()->json(['message' => 'Data siswa berhasil disimpan.', 'data' => $student], 201);
    }

    public function generateToken(Student $student)
    {
        $plainToken = 'SL-' . Str::upper(Str::random(24));
        $student->accessTokens()->where('is_active', true)->update(['is_active' => false]);

        $accessToken = AccessToken::create([
            'student_id' => $student->id,
            'token' => Hash::make($plainToken),
            'expires_at' => now()->addYear(),
            'is_active' => true,
            'created_by' => request()->user()->id,
        ]);

        $emailSent = false;
        if ($student->pendamping_email) {
            try {
                Notification::route('mail', $student->pendamping_email)
                    ->notify(new AccessTokenNotification($student, $plainToken, $accessToken->expires_at->toDateTimeString()));
                $emailSent = true;
            } catch (\Throwable $exception) {
                Log::error('Token dibuat tetapi email pendamping gagal dikirim.', [
                    'student_id' => $student->id,
                    'recipient' => $student->pendamping_email,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => $emailSent
                ? 'Token berhasil dibuat dan dikirim ke email pendamping.'
                : ($student->pendamping_email
                    ? 'Token berhasil dibuat, tetapi email gagal dikirim. Periksa koneksi SMTP Gmail.'
                    : 'Token berhasil dibuat. Kontak email Pendamping belum tersedia.'),
            'email_sent' => $emailSent,
            'token' => $plainToken,
            'expires_at' => $accessToken->expires_at,
        ], 201);
    }

    public function tokens(Student $student)
    {
        return response()->json(['data' => $student->accessTokens()->with('creator:id,name,username')->latest()->get()]);
    }
}
