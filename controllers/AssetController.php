<?php
// ============================================================
//  controllers/AssetController.php
//  CRUD completo de activos tecnológicos
// ============================================================

namespace Controllers;

use Services\AssetService;
use Services\IAService;
use Services\EventReaderService;
use Core\Auth;
use Core\Response;
use Core\Session;

class AssetController
{
    private AssetService $assetService;

    public function __construct()
    {
        Auth::check();
        $this->assetService = new AssetService();
    }

    // GET /assets
    public function index(): void
    {
        $filters = [
            'estado'        => $_GET['estado']        ?? '',
            'asset_type_id' => $_GET['asset_type_id'] ?? '',
            'department_id' => $_GET['department_id'] ?? '',
            'search'        => $_GET['search']        ?? '',
        ];
        $page = (int)($_GET['page'] ?? 1);

        $result   = $this->assetService->getAll($filters, $page);
        $formData = $this->assetService->getFormData();

        Response::layout('assets/index', [
            'title'    => 'Inventario de Activos',
            'assets'   => $result['data'],
            'paging'   => $result,
            'filters'  => $filters,
            'tipos'    => $formData['tipos'],
            'deptos'   => $formData['departamentos'],
            'estados'  => $formData['estados'],
            'user'     => Auth::user(),
            'success'  => Session::getFlash('success'),
            'error'    => Session::getFlash('error'),
        ]);
    }

    // GET /assets/{id}
    public function show(int $id): void
    {
        $asset = $this->assetService->getById($id);
        if (!$asset) {
            Session::flash('error', 'Activo no encontrado.');
            Response::redirect('/assets');
        }

        $history = $this->assetService->getHistory($id);

        // Prediccion IA del activo
        $ia = null;
        try {
            $iaService = new IAService();
            $ia = $iaService->getPredictionByAsset($id);
        } catch (\Exception $e) {
            // IA no disponible — continuar sin prediccion
        }

        Response::layout('assets/detail', [
            'title'   => $asset['nombre'],
            'asset'   => $asset,
            'history' => $history,
            'ia'      => $ia,
            'eventos' => null,
            'user'    => Auth::user(),
        ]);
    }

    // POST /assets/{id}/events — leer eventos del equipo
    public function readEvents(int $id): void
    {
        Auth::requireRole('ti');

        $asset = $this->assetService->getById($id);
        if (!$asset) {
            Response::redirect('/assets');
        }

        $ip       = $_POST['ip']       ?? $asset['ip_address'] ?? '';
        $osType   = $_POST['os_type']  ?? 'windows';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $eventos = null;
        if ($ip && $username) {
            $eventService = new EventReaderService();
            $eventos = $eventService->readEvents($ip, $osType, $username, $password);
        }

        $history = $this->assetService->getHistory($id);

        // Prediccion IA
        $ia = null;
        try {
            $iaService = new IAService();
            $ia = $iaService->getPredictionByAsset($id);
        } catch (\Exception $e) {}

        Response::layout('assets/detail', [
            'title'   => $asset['nombre'],
            'asset'   => $asset,
            'history' => $history,
            'ia'      => $ia,
            'eventos' => $eventos,
            'user'    => Auth::user(),
        ]);
    }

    // GET /assets/create
    public function rdp(int $id): void
    {
        Auth::requireRole('ti');
        $asset = $this->assetService->getById($id);
        if (!$asset || empty($asset['ip_address'])) {
            http_response_code(404);
            echo 'Activo no encontrado o sin IP registrada.';
            return;
        }

        $ip       = $asset['ip_address'];
        $nombre   = preg_replace('/[^a-zA-Z0-9_-]/', '-', $asset['nombre'] ?? 'activo');
        $filename = "rdp-{$nombre}.rdp";

        $rdp = implode("
", [
            'screen mode id:i:2',
            'use multimon:i:0',
            'desktopwidth:i:1920',
            'desktopheight:i:1080',
            'session bpp:i:32',
            "full address:s:{$ip}",
            'username:s:Administrator',
            'prompt for credentials:i:1',
            'audiomode:i:0',
            'redirectprinters:i:0',
            'redirectclipboard:i:1',
            'autoreconnection enabled:i:1',
            'authentication level:i:2',
            'negotiate security layer:i:1',
        ]);

        header('Content-Type: application/rdp');
        header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
        header('Content-Length: ' . strlen($rdp));
        header('Cache-Control: no-cache');
        echo $rdp;
    }

    public function create(): void
    {
        Auth::requireRole('ti');
        $formData = $this->assetService->getFormData();

        Response::layout('assets/form', [
            'title'    => 'Registrar Activo',
            'asset'    => null,
            'formData' => $formData,
            'errors'   => [],
            'user'     => Auth::user(),
        ]);
    }

    // POST /assets/create
    public function store(): void
    {
        Auth::requireRole('ti');
        $result = $this->assetService->create($_POST);

        if (!$result['success']) {
            $formData = $this->assetService->getFormData();
            Response::layout('assets/form', [
                'title'    => 'Registrar Activo',
                'asset'    => null,
                'formData' => $formData,
                'errors'   => $result['errors'] ?? [],
                'old'      => $_POST,
                'user'     => Auth::user(),
            ]);
            return;
        }

        Session::flash('success', 'Activo registrado correctamente.');
        Response::redirect('/assets/' . $result['id']);
    }

    // GET /assets/{id}/edit
    public function edit(int $id): void
    {
        Auth::requireRole('ti');
        $asset = $this->assetService->getById($id);
        if (!$asset) {
            Session::flash('error', 'Activo no encontrado.');
            Response::redirect('/assets');
        }

        $formData = $this->assetService->getFormData();

        Response::layout('assets/form', [
            'title'    => 'Editar Activo',
            'asset'    => $asset,
            'formData' => $formData,
            'errors'   => [],
            'user'     => Auth::user(),
        ]);
    }

    // POST /assets/{id}/edit
    public function update(int $id): void
    {
        Auth::requireRole('ti');
        $result = $this->assetService->update($id, $_POST);

        if (!$result['success']) {
            $formData = $this->assetService->getFormData();
            $asset    = $this->assetService->getById($id);
            Response::layout('assets/form', [
                'title'    => 'Editar Activo',
                'asset'    => $asset,
                'formData' => $formData,
                'errors'   => $result['errors'] ?? [],
                'old'      => $_POST,
                'user'     => Auth::user(),
            ]);
            return;
        }

        Session::flash('success', 'Activo actualizado correctamente.');
        Response::redirect('/assets/' . $id);
    }

    // POST /assets/{id}/delete
    public function delete(int $id): void
    {
        Auth::requireRole('admin');
        $result = $this->assetService->delete($id);

        if (!$result['success']) {
            Session::flash('error', $result['message'] ?? 'No se pudo eliminar el activo.');
        } else {
            Session::flash('success', 'Activo eliminado correctamente.');
        }

        Response::redirect('/assets');
    }
}
