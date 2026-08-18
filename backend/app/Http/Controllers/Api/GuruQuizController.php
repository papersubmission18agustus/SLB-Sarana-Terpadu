<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GuruQuizController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Quiz::with('material:id,title', 'questions.answers')
                ->latest()
                ->get()
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'material_id' => ['required', 'integer', Rule::exists('materials', 'id')],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_text' => ['required', 'string', 'max:1000'],
            'questions.*.answers' => ['required', 'array', 'min:2'],
            'questions.*.answers.*.answer_text' => ['required', 'string', 'max:500'],
            'questions.*.answers.*.is_correct' => ['required', 'boolean'],
        ]);

        if (collect($validated['questions'])->contains(fn (array $question) => ! collect($question['answers'])->contains('is_correct', true))) {
            return response()->json(['message' => 'Setiap soal harus memiliki satu jawaban benar.'], 422);
        }

        $quiz = DB::transaction(function () use ($validated) {
            $quiz = Quiz::create([
                'material_id' => $validated['material_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'passing_score' => $validated['passing_score'],
            ]);

            foreach ($validated['questions'] as $questionIndex => $questionData) {
                $question = $quiz->questions()->create([
                    'question_text' => $questionData['question_text'],
                    'sort_order' => $questionIndex,
                ]);

                foreach ($questionData['answers'] as $answerIndex => $answerData) {
                    $question->answers()->create([
                        'answer_text' => $answerData['answer_text'],
                        'is_correct' => $answerData['is_correct'],
                        'sort_order' => $answerIndex,
                    ]);
                }
            }

            return $quiz;
        });

        return response()->json([
            'message' => 'Kuis berhasil dibuat dan tersedia untuk pendamping.',
            'data' => $quiz->load('material:id,title', 'questions.answers'),
        ], 201);
    }
}
