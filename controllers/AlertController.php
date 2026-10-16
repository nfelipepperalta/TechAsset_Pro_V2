<?php
// ============================================================
//  controllers/AlertController.php
//  Gestión de alertas del sistema
// ============================================================

namespace Controllers;

use Services\AlertService;
use Core\Auth;
use Core\Response;
use Core\Session;

class AlertController
{
    private AlertService $alertService;

    public function __construct()
    {
        Auth::check();
        $this->alertService = new AlertService();
    }

    // GET /alerts
    public function index(): void
    {
        $userId = Auth::user()['id'];
        $alerts = $this->alertService->getPendingByUser($userId);

        Response::layout('alerts/index', [
            'title'   => 'Alertas',
            'alerts'  => $alerts,
            'user'    => Auth::user(),
            'success' => Session::getFlash('success'),
        ]);
    }

    // POST /alerts/{id}/read
    public function markRead(int $id): void
    {
        $this->alertService->markAsRead($id);
        Response::json(['success' => true]);
    }

    // POST /alerts/{id}/resolve
    public function resolve(int $id): void
    {
        $result = $this->alertService->resolve($id);
        Session::flash('success', 'Alerta marcada como resuelta.');
        Response::redirect('/alerts');
    }
}
