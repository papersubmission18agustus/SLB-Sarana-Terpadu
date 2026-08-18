<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningProgress;
use App\Models\Material;
use App\Models\MaterialAccess;
use App\Models\MaterialCategory;
use App\Models\Point;
use App\Models\QuizAttempt;
use App\Models\Student;
use Illuminate\Http\Request;

class GuruDashboardController extends Controller
{
    public function show(Request $request)
    {
        $students = Student::with('currentLevel')->latest()->get();
        $studentIds = $students->pluck('id');
        $progress = LearningProgress::whereIn('student_id', $studentIds)->get()->groupBy('student_id');
        $points = Point::whereIn('student_id', $studentIds)->get()->groupBy('student_id');

        $activeStudentIds = MaterialAccess::whereIn('student_id', $studentIds)
            ->where('accessed_at', '>=', now()->subDay())
            ->pluck('student_id')
            ->merge(
                QuizAttempt::whereIn('student_id', $studentIds)
                    ->where('completed_at', '>=', now()->subDay())
                    ->pluck('student_id')
            )
            ->unique()
            ->values();

        $materialActivities = MaterialAccess::with(['student:id,nama', 'material:id,title'])
            ->whereIn('student_id', $studentIds)
            ->latest('accessed_at')
            ->limit(6)
            ->get()
            ->map(fn (MaterialAccess $access) => [
                'student' => $access->student?->nama,
                'activity' => 'Membuka materi: ' . ($access->material?->title ?? 'Materi'),
                'score' => null,
                'completed_at' => $access->accessed_at,
                'type' => 'material',
            ]);

        $quizActivities = QuizAttempt::with(['student:id,nama', 'quiz:id,title'])
            ->whereIn('student_id', $studentIds)
            ->latest('completed_at')
            ->limit(6)
            ->get()
            ->map(fn (QuizAttempt $attempt) => [
                'student' => $attempt->student?->nama,
                'activity' => 'Mengerjakan kuis: ' . ($attempt->quiz?->title ?? 'Kuis'),
                'score' => (float) $attempt->score,
                'completed_at' => $attempt->completed_at ?? $attempt->created_at,
                'type' => 'quiz',
            ]);

        $recentActivities = $materialActivities
            ->merge($quizActivities)
            ->sortByDesc('completed_at')
            ->take(6)
            ->values();

        return response()->json([
            'data' => [
                'total_students' => $students->count(),
                'active_students' => $activeStudentIds->count(),
                'total_materials' => Material::where('is_published', true)->count(),
                'categories' => MaterialCategory::orderBy('name')->get(['id', 'name', 'slug']),
                'students' => $students->map(function (Student $student) use ($progress, $points) {
                    $studentProgress = $progress->get($student->id, collect());
                    $averageProgress = (int) round($studentProgress->avg('progress_percentage') ?? 0);
                    $totalPoints = (int) $points->get($student->id, collect())->sum('points');

                    return [
                        'id' => $student->id,
                        'name' => $student->nama,
                        'level' => $student->currentLevel?->name ?? 'Pemula',
                        'progress' => $averageProgress,
                        'points' => $totalPoints,
                        'last_activity' => $studentProgress->max('last_activity_at'),
                    ];
                })->values(),
                'recent_activities' => $recentActivities,
            ],
        ]);
    }
}
