<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\LearningProgress;
use App\Models\Material;
use App\Models\Point;
use Illuminate\Http\Request;

class PendampingQuizController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Quiz::with('material:id,title,category_id', 'questions.answers:id,quiz_question_id,answer_text,sort_order')
                ->latest()
                ->get()
                ->values(),
        ]);
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'integer'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $questions = $quiz->load('material', 'questions.answers')->questions;
        $correct = 0;
        $submitted = $validated['answers'];

        foreach ($questions as $question) {
            $answerId = $submitted[$question->id] ?? null;
            $answer = $question->answers->firstWhere('id', $answerId);
            if ($answer?->is_correct) {
                $correct++;
            }
        }

        $total = $questions->count();
        $score = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
        $attempt = QuizAttempt::create([
            'student_id' => $request->user()->id,
            'quiz_id' => $quiz->id,
            'answers' => $submitted,
            'correct_answers' => $correct,
            'total_questions' => $total,
            'score' => $score,
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'completed_at' => now(),
        ]);

        Point::create([
            'student_id' => $request->user()->id,
            'quiz_attempt_id' => $attempt->id,
            'points' => (int) round($score),
            'activity_type' => 'quiz',
            'description' => 'Menyelesaikan kuis ' . $quiz->title,
        ]);

        $categoryId = $quiz->material?->category_id;
        if ($categoryId) {
            $total = Material::where('category_id', $categoryId)->where('is_published', true)->count();
            LearningProgress::updateOrCreate(
                ['student_id' => $request->user()->id, 'category_id' => $categoryId],
                ['total_materials' => $total, 'average_score' => $score, 'last_activity_at' => now()],
            );
        }

        return response()->json([
            'message' => $score >= $quiz->passing_score ? 'Hebat! Kamu lulus kuis.' : 'Tetap semangat, coba pelajari materinya lagi.',
            'data' => [
                'score' => $score,
                'correct_answers' => $correct,
                'total_questions' => $total,
                'passed' => $score >= $quiz->passing_score,
                'attempt_id' => $attempt->id,
            ],
        ], 201);
    }
}
