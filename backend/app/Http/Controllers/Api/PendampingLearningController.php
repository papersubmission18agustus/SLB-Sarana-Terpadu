<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningProgress;
use App\Models\MaterialCategory;
use Illuminate\Http\Request;

class PendampingLearningController extends Controller
{
    public function index(Request $request)
    {
        $studentId = $request->user()->id;
        $progress = LearningProgress::where('student_id', $studentId)
            ->get()
            ->keyBy('category_id');

        $categories = MaterialCategory::with(['materials' => fn ($query) => $query
            ->where('is_published', true)
            ->select(['id', 'category_id', 'title', 'description', 'content', 'pdf_url', 'ppt_url', 'video_url', 'image_path'])])
            ->orderBy('name')
            ->get()
            ->map(function (MaterialCategory $category) use ($progress) {
                $item = $progress->get($category->id);

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'progress' => (int) ($item?->progress_percentage ?? 0),
                    'materials' => $category->materials->map(fn ($material) => [
                        'id' => $material->id,
                        'title' => $material->title,
                        'description' => $material->description,
                        'content' => $material->content,
                        'resources' => array_values(array_filter([
                            ['type' => 'pdf', 'label' => 'Buka PDF', 'url' => $material->pdf_url],
                            ['type' => 'ppt', 'label' => 'Buka PPT', 'url' => $material->ppt_url],
                            ['type' => 'video', 'label' => 'Tonton Video', 'url' => $material->video_url],
                        ], fn (array $resource) => $resource['url'])),
                        'image_path' => $material->image_path,
                    ])->values(),
                ];
            })
            ->values();

        return response()->json(['data' => $categories]);
    }
}
