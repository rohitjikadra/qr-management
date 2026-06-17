<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(AdminDashboardService $dashboard): Response
    {
        return Inertia::render('admin/dashboard', [
            'stats' => $dashboard->stats(),
            'signupsChart' => $dashboard->signupsChart(),
            'qrTypeChart' => $dashboard->qrTypeDistribution(),
        ]);
    }
}
