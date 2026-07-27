<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Services\PrincipalDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PrincipalDashboardService $dashboard): View
    {
        $data = $request->validate(['year' => ['nullable', 'integer', 'exists:academic_years,id']]);

        return view('principal.dashboard', $dashboard->build(isset($data['year']) ? (int) $data['year'] : null));
    }
}
