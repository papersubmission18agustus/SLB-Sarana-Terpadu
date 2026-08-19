<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminStudentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PendampingDashboardController;
use App\Http\Controllers\Api\PendampingLearningController;
use App\Http\Controllers\Api\GuruDashboardController;
use App\Http\Controllers\Api\GuruMaterialController;
use App\Http\Controllers\Api\GuruQuizController;
use App\Http\Controllers\Api\PendampingQuizController;
use App\Http\Controllers\Api\GuruProgressController;
use App\Http\Controllers\Api\PendampingMaterialAccessController;
use App\Http\Controllers\Api\PendampingAiController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\GuruStudentController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login-pendamping', [AuthController::class, 'loginPendamping']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show']);
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::put('/users/{user}', [AdminUserController::class, 'update']);
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
    Route::post('/students', [AdminStudentController::class, 'store']);
    Route::get('/students', [AdminStudentController::class, 'index']);
    Route::post('/students/{student}/generate-token', [AdminStudentController::class, 'generateToken']);
    Route::get('/students/{student}/tokens', [AdminStudentController::class, 'tokens']);
});

Route::middleware(['auth:sanctum', 'role:guru'])->get('/guru/dashboard', [GuruDashboardController::class, 'show']);
Route::middleware(['auth:sanctum', 'role:guru'])->get('/guru/progress', [GuruProgressController::class, 'show']);
Route::middleware(['auth:sanctum', 'role:guru'])->prefix('guru')->group(function () {
    Route::get('/students', [GuruStudentController::class, 'index']);
    Route::get('/students/{student}', [GuruStudentController::class, 'show']);
    Route::get('/students/{student}/progress', [GuruStudentController::class, 'progress']);
    Route::get('/materials', [GuruMaterialController::class, 'index']);
    Route::post('/materials', [GuruMaterialController::class, 'store']);
    Route::get('/quizzes', [GuruQuizController::class, 'index']);
    Route::post('/quizzes', [GuruQuizController::class, 'store']);
});

Route::get('/user', fn (Request $request) => $request->user())->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/pendamping/dashboard', [PendampingDashboardController::class, 'show']);
Route::middleware('auth:sanctum')->get('/pendamping/learning', [PendampingLearningController::class, 'index']);
Route::middleware('auth:sanctum')->prefix('pendamping')->group(function () {
    Route::get('/quizzes', [PendampingQuizController::class, 'index']);
    Route::post('/quizzes/{quiz}/submit', [PendampingQuizController::class, 'submit']);
    Route::post('/materials/{material}/access', [PendampingMaterialAccessController::class, 'store']);
    Route::middleware('role:pendamping')->post('/ai/ask', [PendampingAiController::class, 'ask']);
});
