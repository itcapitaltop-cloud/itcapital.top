<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

class AcademyNewsController extends Controller
{
    public function index(): View
    {
        return view('academy.news.index', [
            'newsItems' => News::query()
                ->published()
                ->latest('published_at')
                ->paginate(9),
        ]);
    }

    public function show(News $news): View
    {
        abort_if(is_null($news->published_at), Response::HTTP_NOT_FOUND);

        return view('academy.news.show', [
            'news' => $news,
        ]);
    }
}
