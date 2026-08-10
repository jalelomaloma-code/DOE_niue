<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Contracts\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        return view('projects.index', [
            'projects' => Project::published()
                ->with(['programme', 'media'])
                ->latest('published_at')
                ->paginate(9),
        ]);
    }

    public function show(string $slug): View
    {
        $project = Project::published()
            ->with(['programme', 'media'])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('projects.show', ['project' => $project]);
    }
}
