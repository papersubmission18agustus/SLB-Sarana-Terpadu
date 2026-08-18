<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningProgress;
use App\Models\Material;
use App\Models\MaterialAccess;
use Illuminate\Http\Request;

class PendampingMaterialAccessController extends Controller
{
    public function store(Request $request, Material $material)
    {
        MaterialAccess::create([
            'student_id' => $request->user()->id,
            'material_id' => $material->id,
            'accessed_at' => now(),
        ]);

        $total = Material::where('category_id', $material->category_id)->where('is_published', true)->count();
        $completed = MaterialAccess::where('student_id', $request->user()->id)
            ->whereHas('material', fn ($query) => $query->where('category_id', $material->category_id))
            ->distinct('material_id')->count('material_id');

        LearningProgress::updateOrCreate(
            ['student_id' => $request->user()->id, 'category_id' => $material->category_id],
            [
                'completed_materials' => $completed,
                'total_materials' => $total,
                'progress_percentage' => $total ? round(($completed / $total) * 100, 2) : 0,
                'last_activity_at' => now(),
            ],
        );

        return response()->json(['message' => 'Akses materi tercatat.', 'progress' => $total ? round(($completed / $total) * 100, 2) : 0]);
    }
}
