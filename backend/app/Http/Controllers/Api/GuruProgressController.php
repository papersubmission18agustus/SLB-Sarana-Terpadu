<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningProgress;
use App\Models\Material;
use App\Models\MaterialAccess;
use App\Models\QuizAttempt;
use App\Models\Student;
use Illuminate\Http\Request;

class GuruProgressController extends Controller
{
    public function show(Request $request)
    {
        $students = Student::with('currentLevel')->latest()->get();
        $studentIds = $students->pluck('id');
        $progress = LearningProgress::with('category:id,name,slug')->whereIn('student_id', $studentIds)->get();
        $attempts = QuizAttempt::with(['student:id,nama', 'quiz:id,title,material_id', 'quiz.material:id,title,category_id'])
            ->whereIn('student_id', $studentIds)->latest()->get();
        $accesses = MaterialAccess::whereIn('student_id', $studentIds)->latest('accessed_at')->get();

        $subjectPerformance = $progress->groupBy('category_id')->map(function ($items) {
            return [
                'name' => $items->first()->category?->name ?? 'Pelajaran',
                'progress' => (int) round($items->avg('progress_percentage') ?? 0),
                'average_score' => (int) round($items->avg('average_score') ?? 0),
            ];
        })->values();

        $studentRows = $students->map(function (Student $student) use ($progress, $attempts, $accesses) {
            $studentProgress = $progress->where('student_id', $student->id);
            $studentAttempts = $attempts->where('student_id', $student->id);
            $lastAccess = $accesses->firstWhere('student_id', $student->id);
            $average = (int) round($studentAttempts->avg('score') ?? $studentProgress->avg('average_score') ?? 0);
            $lastActivity = $lastAccess?->accessed_at ?? $studentAttempts->first()?->completed_at;

            return [
                'id' => $student->id,
                'name' => $student->nama,
                'level' => $student->currentLevel?->name ?? 'Pemula',
                'progress' => (int) round($studentProgress->avg('progress_percentage') ?? 0),
                'average_score' => $average,
                'materials_accessed' => $accesses->where('student_id', $student->id)->unique('material_id')->count(),
                'quizzes_completed' => $studentAttempts->count(),
                'last_activity' => $lastActivity,
            ];
        })->values();

        return response()->json([
            'data' => [
                'subject_performance' => $subjectPerformance,
                'students' => $studentRows,
                'status' => [
                    'on_track' => $studentRows->where('progress', '>=', 60)->count(),
                    'needs_help' => $studentRows->whereBetween('progress', [1, 59])->count(),
                    'inactive' => $studentRows->where('progress', 0)->count(),
                ],
                'activities' => $attempts->take(10)->map(fn (QuizAttempt $attempt) => [
                    'student' => $attempt->student?->nama,
                    'activity' => $attempt->quiz?->title ?? 'Kuis',
                    'score' => (float) $attempt->score,
                    'completed_at' => $attempt->completed_at,
                ])->values(),
            ],
        ]);
    }
}
