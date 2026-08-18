<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiInteraction;
use App\Services\GeminiService;
use App\Services\RuleBasedGuardrail;
use Illuminate\Http\Request;
use Throwable;

class PendampingAiController extends Controller
{
    public function ask(Request $request, RuleBasedGuardrail $guardrail, GeminiService $gemini)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:500'],
            'material_title' => ['nullable', 'string', 'max:150'],
            'material_id' => ['nullable', 'integer', 'exists:materials,id'],
        ]);

        $check = $guardrail->check($validated['question']);
        if (! $check['allowed']) {
            return response()->json(['message' => $check['message'], 'guardrail' => true], 422);
        }

        try {
            $answer = $gemini->answer($validated['question'], $validated['material_title'] ?? null);
        } catch (Throwable $exception) {
            report($exception);
            $message = str_contains($exception->getMessage(), 'HTTP 429')
                ? 'Kuota Gemini sedang habis atau terlalu banyak request. Coba lagi setelah quota reset.'
                : 'Asisten sedang beristirahat. Coba lagi sebentar, ya.';
            return response()->json(['message' => $message], 503);
        }

        AiInteraction::create([
            'student_id' => $request->user()->id,
            'material_id' => $validated['material_id'] ?? null,
            'performance_score' => 0,
            'level' => 'L1',
            'prompt' => $validated['question'],
            'response' => $answer,
        ]);

        return response()->json(['data' => ['answer' => $answer]]);
    }
}
