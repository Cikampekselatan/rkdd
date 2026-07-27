<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StudentDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, StudentDashboardService $dashboard): View
    {
        $this->authorize('viewStudentDashboard', User::class);

        return view('student.dashboard', $dashboard->build($request->user()));
    }
}
