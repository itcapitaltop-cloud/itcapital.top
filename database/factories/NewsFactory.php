<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NewsCategoryEnum;
use App\Models\News;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<News>
 */
class NewsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => fake()->randomElement(NewsCategoryEnum::cases())->value,
            'title' => [
                'ru' => fake()->text(80),
                'en' => fake()->text(80),
                'zh' => '市场新闻标题',
            ],
            'mobile_preview' => [
                'ru' => fake()->text(100),
                'en' => fake()->text(100),
                'zh' => '移动端简短预览文本',
            ],
            'web_preview' => [
                'ru' => fake()->text(220),
                'en' => fake()->text(220),
                'zh' => '网页端预览文本，长度稍长一些。',
            ],
            'content' => [
                'ru' => fake()->paragraphs(3, true),
                'en' => fake()->paragraphs(3, true),
                'zh' => '这是中文版本的新闻正文。',
            ],
            'image' => 'news/test-image.jpg',
            'published_at' => now(),
        ];
    }

    public function unpublished(): static
    {
        return $this->afterCreating(function (News $news): void {
            $news->forceFill([
                'published_at' => null,
            ])->saveQuietly();
        });
    }
}
