<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\TeacherDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TeacherDashboardService $dashboard): View
    {
        $data = $request->validate(['year' => ['nullable', 'integer', 'exists:academic_years,id']]);

        return view('teacher.dashboard', $dashboard->build(isset($data['year']) ? (int) $data['year'] : null));
    }
}
