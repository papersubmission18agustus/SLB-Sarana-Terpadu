<?php

namespace App\Services;

class RuleBasedGuardrail
{
    public function check(string $question): array
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $question)));

        if (mb_strlen($normalized) < 3 || mb_strlen($normalized) > 500) {
            return ['allowed' => false, 'message' => 'Tulis pertanyaan belajar yang singkat, ya.'];
        }

        $blockedPatterns = [
            '/\b(seks|porno|telanjang|judi|narkoba|bunuh|lukai|senjata)\b/u',
            '/\b(password|kata sandi|api key|token|credential|rahasia)\b/u',
            '/\b(bypass|abaikan aturan|ignore (all|previous)|system prompt|jailbreak)\b/u',
            '/\b(diagnosis|resep obat|dosis obat|pengacara|nasihat hukum)\b/u',
        ];

        foreach ($blockedPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return ['allowed' => false, 'message' => 'Aku hanya bisa membantu menjelaskan materi belajar dengan cara yang aman.'];
            }
        }

        $learningWords = ['belajar', 'materi', 'pelajaran', 'kuis', 'angka', 'matematika', 'bahasa', 'huruf', 'warna', 'bentuk', 'hitung', 'menghitung', 'soal', 'jelaskan', 'jelaskanlah', 'contoh', 'math', 'learn', 'lesson', 'what is', 'how many', 'help me'];
        $hasSimpleMath = (bool) preg_match('/\b(berapa|what is|hitung|calculate)\b.*\d+\s*[+\-*\/]\s*\d+/u', $normalized);
        $isLearningQuestion = $hasSimpleMath || collect($learningWords)->contains(fn (string $word) => str_contains($normalized, $word));

        if (! $isLearningQuestion) {
            return ['allowed' => false, 'message' => 'Coba tanyakan tentang materi, kuis, angka, huruf, warna, atau bentuk yang sedang dipelajari.'];
        }

        return ['allowed' => true, 'message' => null];
    }
}
