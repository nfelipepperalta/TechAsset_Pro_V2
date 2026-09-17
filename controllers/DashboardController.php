<?php
// ============================================================
//  controllers/DashboardController.php
//  Panel principal de métricas
// ============================================================

namespace Controllers;

use Services\DashboardService;
use Core\Auth;
use Core\Response;

class DashboardController
{
    private DashboardService $dashService;

    public function __construct()
    {
        Auth::check();
        $this->dashService = new DashboardService();
    }

    // GET /dashboard
    public function index(): void
    {
        $userId = Auth::user()['id'];
        $data   = $this->dashService->getData($userId);

        Response::layout('dashboard/index', [
            'title' => 'Dashboard',
            'data'  => $data,
            'user'  => Auth::user(),
        ]);
    }
}
