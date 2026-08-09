<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use Illuminate\Contracts\View\View;

class ProgrammeController extends Controller
{
    public function show(string $slug): View
    {
        $programme = Programme::published()->with('media')->where('slug', $slug)->firstOrFail();

        return view('programmes.show', [
            'programme' => $programme,
            'projects' => $programme->projects()
                ->published()
                ->with('media')
                ->latest('published_at')
                ->take(6)
                ->get(),
        ]);
    }
}
