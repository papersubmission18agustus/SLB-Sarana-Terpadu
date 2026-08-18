<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GuruMaterialController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Material::with('category:id,name,slug')
                ->where('is_published', true)
                ->latest()
                ->get()
                ->map(fn (Material $material) => $this->present($material))
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'integer', Rule::exists('material_categories', 'id')],
            'type' => ['required', Rule::in(['pdf', 'ppt', 'youtube'])],
            'file' => ['required_unless:type,youtube', 'file', 'max:20480', 'extensions:pdf,ppt,pptx'],
            'youtube_url' => ['required_if:type,youtube', 'nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $material = new Material();
        $material->category_id = $validated['category_id'];
        $material->created_by = $request->user()->id;
        $material->title = $validated['title'];
        $material->description = $validated['description'] ?? null;
        $material->content = null;
        $material->is_published = true;

        if ($validated['type'] === 'youtube') {
            $material->video_url = $validated['youtube_url'];
        } else {
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            $path = $file->storeAs('materials', Str::uuid() . '.' . $extension, 'public');
            $baseUrl = rtrim((string) env('APP_URL', 'http://localhost'), '/');
            $url = $baseUrl . '/storage/' . ltrim($path, '/');
            if ($validated['type'] === 'pdf') {
                $material->pdf_url = $url;
            } else {
                $material->ppt_url = $url;
            }
        }

        $material->save();

        return response()->json([
            'message' => 'Materi berhasil diunggah dan tersedia untuk pendamping.',
            'data' => $this->present($material->load('category:id,name,slug')),
        ], 201);
    }

    private function present(Material $material): array
    {
        return [
            'id' => $material->id,
            'title' => $material->title,
            'description' => $material->description,
            'category' => $material->category?->name,
            'type' => $material->pdf_url ? 'pdf' : ($material->ppt_url ? 'ppt' : 'youtube'),
            'url' => $material->pdf_url ?? $material->ppt_url ?? $material->video_url,
            'created_at' => $material->created_at,
        ];
    }
}
