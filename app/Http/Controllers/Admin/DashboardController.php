<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;

class DashboardController extends Controller
{
    /**
     * Dashboard admin: pekerjaan tertunda, sesi aktif, pipeline sertifikasi, materai,
     * statistik & aktivitas terbaru (lihat AdminDashboardService).
     */
    public function __invoke(AdminDashboardService $dashboard)
    {
        return inertia('Admin/Dashboard/Index', $dashboard->build());
    }
}
