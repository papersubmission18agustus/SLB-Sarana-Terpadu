<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningProgress;
use App\Models\MaterialAccess;
use App\Models\QuizAttempt;
use App\Models\Student;
use Illuminate\Http\Request;

class GuruStudentController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $studentsQuery = Student::with('currentLevel')
            ->when($search !== '', fn ($query) => $query->where('nama', 'like', "%{$search}%"))
            ->latest();

        $students = $studentsQuery->get()->map(function (Student $student) {
            $progressAverage = (int) round(LearningProgress::where('student_id', $student->id)->avg('progress_percentage') ?? 0);
            $lastMaterialAccess = MaterialAccess::where('student_id', $student->id)
                ->latest('accessed_at')
                ->value('accessed_at');
            $lastQuizAttempt = QuizAttempt::where('student_id', $student->id)
                ->latest('completed_at')
                ->value('completed_at');

            return [
                'id' => $student->id,
                'name' => $student->nama,
                'level' => $student->currentLevel?->name ?? 'Pemula',
                'progress' => $progressAverage,
                'materials_accessed' => MaterialAccess::where('student_id', $student->id)->distinct('material_id')->count('material_id'),
                'quizzes_completed' => QuizAttempt::where('student_id', $student->id)->count(),
                'last_activity' => $lastMaterialAccess ?? $lastQuizAttempt,
            ];
        })->values();

        return response()->json([
            'data' => $students,
        ]);
    }

    public function show(Student $student)
    {
        $progress = LearningProgress::with('category:id,name,slug')
            ->where('student_id', $student->id)
            ->get();

        $attempts = QuizAttempt::with([
            'quiz:id,title,material_id',
            'quiz.material:id,title,category_id',
        ])
            ->where('student_id', $student->id)
            ->latest('completed_at')
            ->get();

        $materialAccesses = MaterialAccess::with('material:id,title,category_id')
            ->where('student_id', $student->id)
            ->latest('accessed_at')
            ->get();

        $subjectProgress = $progress->groupBy('category_id')->map(function ($items) {
            return [
                'name' => $items->first()->category?->name ?? 'Pelajaran',
                'slug' => $items->first()->category?->slug ?? 'pelajaran',
                'progress' => (int) round($items->avg('progress_percentage') ?? 0),
                'average_score' => (int) round($items->avg('average_score') ?? 0),
                'completed_materials' => (int) $items->sum('completed_materials'),
                'total_materials' => (int) $items->sum('total_materials'),
            ];
        })->values();

        $activities = collect()
            ->merge($materialAccesses->map(function (MaterialAccess $item) {
                return [
                    'type' => 'Membuka materi',
                    'label' => $item->material?->title ?? 'Materi',
                    'time' => $item->accessed_at,
                ];
            }))
            ->merge($attempts->map(function (QuizAttempt $item) {
                return [
                    'type' => 'Mengerjakan kuis',
                    'label' => $item->quiz?->title ?? 'Kuis',
                    'time' => $item->completed_at ?? $item->created_at,
                    'score' => (float) $item->score,
                ];
            }))
            ->sortByDesc('time')
            ->take(10)
            ->values();

        return response()->json([
            'data' => [
                'id' => $student->id,
                'name' => $student->nama,
                'tanggal_lahir' => $student->tanggal_lahir,
                'tempat_lahir' => $student->tempat_lahir,
                'nama_orang_tua_wali' => $student->nama_orang_tua_wali,
                'pendamping_email' => $student->pendamping_email,
                'pendamping_phone' => $student->pendamping_phone,
                'current_level' => [
                    'id' => $student->currentLevel?->id,
                    'name' => $student->currentLevel?->name ?? 'Pemula',
                    'minimum_points' => $student->currentLevel?->minimum_points ?? 0,
                ],
                'summary' => [
                    'progress' => (int) round($progress->avg('progress_percentage') ?? 0),
                    'average_score' => (int) round($attempts->avg('score') ?? 0),
                    'materials_accessed' => $materialAccesses->unique('material_id')->count(),
                    'quizzes_completed' => $attempts->count(),
                ],
                'progress_by_subject' => $subjectProgress,
                'activities' => $activities,
                'scores' => $attempts->map(function (QuizAttempt $attempt) {
                    return [
                        'title' => $attempt->quiz?->title ?? 'Kuis',
                        'score' => (float) $attempt->score,
                        'completed_at' => $attempt->completed_at,
                    ];
                })->values(),
                'history' => $activities,
            ],
        ]);
    }

    public function progress(Student $student)
    {
        return $this->show($student);
    }
}
