<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Contracts\View\View;

class ResourceController extends Controller
{
    public function index(): View
    {
        return view('resources.index', [
            'categories' => DocumentCategory::orderBy('sort_order')->get(),
            'documents' => Document::published()
                ->with(['category', 'media'])
                ->latest('published_date')
                ->paginate(12),
        ]);
    }
}
