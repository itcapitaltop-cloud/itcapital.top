<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title', 100);
            $table->string('mobile_preview', 120);
            $table->string('web_preview', 300);
            $table->text('content');
            $table->timestamps();

            $table->unique(['news_id', 'locale']);
            $table->index(['locale', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_translations');
    }
};
