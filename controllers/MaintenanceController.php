<?php
// ============================================================
//  controllers/MaintenanceController.php
//  CRUD de mantenimientos
// ============================================================

namespace Controllers;

use Services\MaintenanceService;
use Services\AssetService;
use Core\Auth;
use Core\Response;
use Core\Session;

class MaintenanceController
{
    private MaintenanceService $mainService;
    private AssetService       $assetService;

    public function __construct()
    {
        Auth::check();
        $this->mainService  = new MaintenanceService();
        $this->assetService = new AssetService();
    }

    // GET /maintenance
    public function index(): void
    {
        $upcoming = $this->mainService->getUpcoming(30);

        Response::layout('maintenance/index', [
            'title'    => 'Mantenimientos',
            'upcoming' => $upcoming,
            'user'     => Auth::user(),
            'success'  => Session::getFlash('success'),
            'error'    => Session::getFlash('error'),
        ]);
    }

    // GET /maintenance/create
    public function create(): void
    {
        Auth::requireRole('ti');

        // Permitir preseleccionar un activo desde la URL
        $assetId = (int)($_GET['asset_id'] ?? 0);
        $asset   = $assetId ? $this->assetService->getById($assetId) : null;
        $assets  = !$asset ? $this->assetService->getAll(['estado' => 'activo'])['data'] ?? [] : [];

        Response::layout('maintenance/form', [
            'title'    => 'Registrar Mantenimiento',
            'maint'    => null,
            'asset'    => $asset,
            'assets'   => $assets ?? [],
            'errors'   => [],
            'user'     => Auth::user(),
        ]);
    }

    // POST /maintenance/create
    public function store(): void
    {
        Auth::requireRole('ti');
        $result = $this->mainService->create($_POST);

        if (!$result['success']) {
            $assetId = (int)($_POST['asset_id'] ?? 0);
            $asset   = $assetId ? $this->assetService->getById($assetId) : null;
            Response::layout('maintenance/form', [
                'title'  => 'Registrar Mantenimiento',
                'maint'  => null,
                'asset'  => $asset,
                'errors' => $result['errors'] ?? [],
                'old'    => $_POST,
                'user'   => Auth::user(),
            ]);
            return;
        }

        Session::flash('success', 'Mantenimiento registrado correctamente.');

        // Redirigir al detalle del activo si viene de ahí
        $assetId = (int)($_POST['asset_id'] ?? 0);
        $redirect = $assetId ? '/assets/' . $assetId : '/maintenance';
        Response::redirect($redirect);
    }

    // GET /maintenance/{id}/edit
    public function edit(int $id): void
    {
        Auth::requireRole('ti');
        // Reutilizamos el repo desde el service
        $maint = (new \Repositories\MaintenanceRepository())->findById($id);
        if (!$maint) {
            Session::flash('error', 'Mantenimiento no encontrado.');
            Response::redirect('/maintenance');
        }

        $asset = $this->assetService->getById($maint['asset_id']);

        Response::layout('maintenance/form', [
            'title'  => 'Editar Mantenimiento',
            'maint'  => $maint,
            'asset'  => $asset,
            'errors' => [],
            'user'   => Auth::user(),
        ]);
    }

    // POST /maintenance/{id}/edit
    public function update(int $id): void
    {
        Auth::requireRole('ti');
        $result = $this->mainService->update($id, $_POST);

        if (!$result['success']) {
            $maint = (new \Repositories\MaintenanceRepository())->findById($id);
            $asset = $maint ? $this->assetService->getById($maint['asset_id']) : null;
            Response::layout('maintenance/form', [
                'title'  => 'Editar Mantenimiento',
                'maint'  => $maint,
                'asset'  => $asset,
                'errors' => $result['errors'] ?? [],
                'old'    => $_POST,
                'user'   => Auth::user(),
            ]);
            return;
        }

        Session::flash('success', 'Mantenimiento actualizado correctamente.');
        $assetId  = (int)($_POST['asset_id'] ?? 0);
        $redirect = $assetId ? '/assets/' . $assetId : '/maintenance';
        Response::redirect($redirect);
    }

    // POST /maintenance/{id}/delete
    public function delete(int $id): void
    {
        Auth::requireRole('ti');
        $result = $this->mainService->delete($id);

        if (!$result['success']) {
            Session::flash('error', $result['message'] ?? 'No se pudo eliminar.');
        } else {
            Session::flash('success', 'Mantenimiento eliminado.');
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/maintenance';
        header("Location: $referer");
        exit;
    }
}
