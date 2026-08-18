<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\MaterialAccess;
use App\Models\Point;
use App\Models\QuizAttempt;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PendampingDashboardController extends Controller
{
    public function show(Request $request)
    {
        $student = $request->user()->load('currentLevel');
        $points = (int) Point::where('student_id', $student->id)->sum('points');
        $currentLevel = $student->currentLevel;
        $nextLevel = Level::query()
            ->where('minimum_points', '>', $currentLevel?->minimum_points ?? 0)
            ->orderBy('minimum_points')
            ->first();

        $levelStart = (int) ($currentLevel?->minimum_points ?? 0);
        $levelTarget = (int) ($nextLevel?->minimum_points ?? max($levelStart + 100, $points + 100));
        $levelProgress = min(100, max(0, (int) round((($points - $levelStart) / max(1, $levelTarget - $levelStart)) * 100)));

        $today = Carbon::now();
        $calendarStart = $today->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $today->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $studyDates = MaterialAccess::where('student_id', $student->id)
            ->selectRaw('DATE(accessed_at) as activity_date')
            ->unionAll(
                QuizAttempt::where('student_id', $student->id)
                    ->selectRaw('DATE(completed_at) as activity_date')
            )
            ->pluck('activity_date')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->unique()
            ->values();

        $calendarDays = [];
        $current = $calendarStart->copy();
        while ($current->lessThanOrEqualTo($calendarEnd)) {
            $dateKey = $current->format('Y-m-d');
            $calendarDays[] = [
                'date' => $dateKey,
                'day' => $current->day,
                'in_month' => $current->month === $today->month,
                'is_today' => $dateKey === $today->format('Y-m-d'),
                'is_active' => $studyDates->contains($dateKey),
            ];
            $current->addDay();
        }

        return response()->json([
            'data' => [
                'student' => $student->only(['id', 'nama', 'current_level_id', 'currentLevel']),
                'level' => [
                    'name' => $currentLevel?->name ?? 'Pemula',
                    'points' => $points,
                    'target_points' => $levelTarget,
                    'progress' => $levelProgress,
                    'next_level' => $nextLevel?->name,
                ],
                'streak_days' => 0,
                'weekly_minutes' => 0,
                'completed_quizzes' => QuizAttempt::where('student_id', $student->id)->count(),
                'progress' => LearningProgress::with('category:id,name,slug')
                    ->where('student_id', $student->id)
                    ->orderByDesc('progress_percentage')
                    ->get()
                    ->map(fn (LearningProgress $item) => [
                        'name' => $item->category?->name ?? 'Pelajaran',
                        'slug' => $item->category?->slug,
                        'percentage' => (int) $item->progress_percentage,
                        'completed' => $item->completed_materials,
                        'total' => $item->total_materials,
                    ])
                    ->values(),
                'calendar' => [
                    'year' => $today->year,
                    'month' => $today->month,
                    'month_label' => $today->translatedFormat('F Y'),
                    'days' => $calendarDays,
                ],
                'badges' => $student->badges()
                    ->orderByPivot('awarded_at', 'desc')
                    ->limit(3)
                    ->get(['badges.id', 'badges.name', 'badges.slug', 'badges.icon_path'])
                    ->values(),
                'recent_points' => Point::where('student_id', $student->id)
                    ->latest()
                    ->limit(5)
                    ->get(['id', 'points', 'activity_type', 'description', 'created_at']),
                'available_badges' => Badge::query()
                    ->where('required_points', '<=', $points)
                    ->count(),
            ],
        ]);
    }
}
