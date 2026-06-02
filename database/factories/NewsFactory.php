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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => fake()->randomElement(NewsCategoryEnum::cases())->value,
            'image' => 'news/test-image.jpg',
            'published_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (News $news): void {
            $news->translations()->createMany([
                [
                    'locale' => 'ru',
                    'title' => fake()->text(80),
                    'mobile_preview' => fake()->text(100),
                    'web_preview' => fake()->text(220),
                    'content' => fake()->paragraphs(3, true),
                ],
                [
                    'locale' => 'en',
                    'title' => fake()->text(80),
                    'mobile_preview' => fake()->text(100),
                    'web_preview' => fake()->text(220),
                    'content' => fake()->paragraphs(3, true),
                ],
                [
                    'locale' => 'zh',
                    'title' => '市场新闻标题',
                    'mobile_preview' => '移动端简短预览文本',
                    'web_preview' => '网页端预览文本，长度稍长一些。',
                    'content' => '这是中文版本的新闻正文。',
                ],
            ]);
        });
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
