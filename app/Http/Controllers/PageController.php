<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function show(string $path): View
    {
        $page = Page::published()->where('path', $path)->firstOrFail();

        return view('pages.show', [
            'page' => $page,
            'children' => $page->children()->published()->sectionNav()->get(),
            'siblings' => $page->parent_id
                ? Page::published()->sectionNav()->where('parent_id', $page->parent_id)->orderBy('sort_order')->get()
                : collect(),
        ]);
    }
}
