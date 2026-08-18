<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    public function answer(string $question, ?string $materialTitle = null): string
    {
        $apiKey = config('services.gemini.key');
        $url = config('services.gemini.url');

        if (! $apiKey || ! $url) {
            throw new RuntimeException('Layanan AI belum dikonfigurasi.');
        }

        $context = $materialTitle ? "Materi yang sedang dibahas: {$materialTitle}." : 'Tidak ada materi spesifik yang dipilih.';
        $prompt = "Kamu adalah asisten belajar ramah anak untuk pendamping siswa. {$context} Jawab dalam Bahasa Indonesia yang sederhana, maksimal 3 paragraf pendek. Beri contoh langkah demi langkah bila perlu. Jangan mengarang fakta, jangan membahas topik di luar pembelajaran, dan jangan meminta data pribadi. Pertanyaan: {$question}";

        $response = Http::timeout(20)->acceptJson()->post($url . '?key=' . urlencode($apiKey), [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 300],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini request failed with HTTP ' . $response->status() . '.');
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Jawaban AI kosong.');
        }

        return trim($text);
    }
}
