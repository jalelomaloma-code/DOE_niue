<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;

class ReportIssueController extends Controller
{
    public function show(): View
    {
        return view('report-issue', [
            'settings' => SiteSetting::current(),
        ]);
    }
}
