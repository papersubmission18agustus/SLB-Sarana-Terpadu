<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->longText('content')->nullable()->after('description');
            $table->string('pdf_url')->nullable()->after('content');
            $table->string('ppt_url')->nullable()->after('pdf_url');
            $table->string('video_url')->nullable()->after('ppt_url');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['content', 'pdf_url', 'ppt_url', 'video_url']);
        });
    }
};
