<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->timestamp('accessed_at');
            $table->timestamps();
            $table->index(['student_id', 'material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_accesses');
    }
};
