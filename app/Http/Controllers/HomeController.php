<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\HomepageSetting;
use App\Models\NewsArticle;
use App\Models\Programme;
use App\Models\Project;
use App\Models\QuickLink;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'homepage' => HomepageSetting::current(),
            'quickLinks' => QuickLink::active()->get(),
            'programmes' => $this->featuredOrLatest(Programme::query(), 4, 'sort_order'),
            'news' => NewsArticle::published()->with('category')->latest('published_at')->take(4)->get(),
            'projects' => $this->featuredOrLatest(Project::query(), 3),
            'documents' => Document::published()->with('category')->latest('published_date')->take(5)->get(),
        ]);
    }

    /**
     * Featured items first; if nothing is flagged, fall back to the most
     * recent so a section never sits empty merely because nobody ticked a box.
     */
    private function featuredOrLatest(
        \Illuminate\Database\Eloquent\Builder $query,
        int $limit,
        ?string $orderColumn = null,
    ): \Illuminate\Database\Eloquent\Collection {
        $featured = (clone $query)->published()->featured();

        $featured = $orderColumn
            ? $featured->orderBy($orderColumn)
            : $featured->latest('published_at');

        $results = $featured->take($limit)->get();

        return $results->isNotEmpty()
            ? $results
            : $query->published()->latest('published_at')->take($limit)->get();
    }
}
