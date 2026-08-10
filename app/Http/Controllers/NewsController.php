<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use Illuminate\Contracts\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        return view('news.index', [
            'articles' => NewsArticle::published()
                ->with(['category', 'media'])
                ->latest('published_at')
                ->paginate(9),
        ]);
    }

    public function show(string $slug): View
    {
        $article = NewsArticle::published()
            ->with(['category', 'media'])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('news.show', ['article' => $article]);
    }
}
