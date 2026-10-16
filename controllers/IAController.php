<?php
// ============================================================
//  controllers/IAController.php
//  Módulo de IA Predictiva de Fallos
// ============================================================

namespace Controllers;

use Services\IAService;
use Core\Auth;
use Core\Response;
use Core\Session;

class IAController
{
    private IAService $iaService;

    public function __construct()
    {
        Auth::check();
        $this->iaService = new IAService();
    }

    // GET /ia — dashboard principal de IA
    public function index(): void
    {
        $data    = $this->iaService->getPredictions();
        $resumen = $this->iaService->getResumen();
        $top     = $this->iaService->getTopRiesgo(10);

        Response::layout('ia/index', [
            'title'   => 'IA Predictiva de Fallos',
            'data'    => $data,
            'resumen' => $resumen,
            'top'     => $top,
            'user'    => Auth::user(),
            'success' => Session::getFlash('success'),
            'error'   => Session::getFlash('error'),
        ]);
    }

    // GET /ia/asset/{id} — predicción de un activo específico
    public function asset(int $id): void
    {
        $prediccion = $this->iaService->getPredictionByAsset($id);

        if (!$prediccion) {
            Response::json(['error' => 'Activo no encontrado'], 404);
        }

        Response::json(['success' => true, 'data' => $prediccion]);
    }

    // POST /ia/refresh — forzar re-entrenamiento
    public function refresh(): void
    {
        Auth::requireRole('ti');
        $result = $this->iaService->refresh();

        if (isset($result['error'])) {
            Session::flash('error', 'Error al actualizar el modelo: ' . $result['error']);
        } else {
            Session::flash('success', 'Modelo de IA actualizado correctamente.');
        }

        Response::redirect('/ia');
    }
}
