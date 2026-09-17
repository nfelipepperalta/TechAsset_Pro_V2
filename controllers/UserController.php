<?php
// ============================================================
//  controllers/UserController.php
//  Gestión de usuarios del sistema
// ============================================================

namespace Controllers;

use Services\UserService;
use Core\Auth;
use Core\Response;
use Core\Session;

class UserController
{
    private UserService $userService;

    public function __construct()
    {
        Auth::requireRole('admin');
        $this->userService = new UserService();
    }

    // GET /users
    public function index(): void
    {
        $users = $this->userService->getAll();

        Response::layout('users/index', [
            'title'   => 'Usuarios',
            'users'   => $users,
            'user'    => Auth::user(),
            'success' => Session::getFlash('success'),
            'error'   => Session::getFlash('error'),
        ]);
    }

    // GET /users/create
    public function create(): void
    {
        $formData = $this->userService->getFormData();

        Response::layout('users/form', [
            'title'    => 'Crear Usuario',
            'record'   => null,
            'formData' => $formData,
            'errors'   => [],
            'user'     => Auth::user(),
        ]);
    }

    // POST /users/create
    public function store(): void
    {
        $result = $this->userService->create($_POST);

        if (!$result['success']) {
            $formData = $this->userService->getFormData();
            Response::layout('users/form', [
                'title'    => 'Crear Usuario',
                'record'   => null,
                'formData' => $formData,
                'errors'   => $result['errors'] ?? [],
                'old'      => $_POST,
                'user'     => Auth::user(),
            ]);
            return;
        }

        Session::flash('success', 'Usuario creado correctamente.');
        Response::redirect('/users');
    }

    // GET /users/{id}/edit
    public function edit(int $id): void
    {
        $record   = $this->userService->getById($id);
        if (!$record) {
            Session::flash('error', 'Usuario no encontrado.');
            Response::redirect('/users');
        }

        $formData = $this->userService->getFormData();

        Response::layout('users/form', [
            'title'    => 'Editar Usuario',
            'record'   => $record,
            'formData' => $formData,
            'errors'   => [],
            'user'     => Auth::user(),
        ]);
    }

    // POST /users/{id}/edit
    public function update(int $id): void
    {
        $result = $this->userService->update($id, $_POST);

        if (!$result['success']) {
            $formData = $this->userService->getFormData();
            $record   = $this->userService->getById($id);
            Response::layout('users/form', [
                'title'    => 'Editar Usuario',
                'record'   => $record,
                'formData' => $formData,
                'errors'   => $result['errors'] ?? [],
                'old'      => $_POST,
                'user'     => Auth::user(),
            ]);
            return;
        }

        Session::flash('success', 'Usuario actualizado correctamente.');
        Response::redirect('/users');
    }

    // POST /users/{id}/delete
    public function delete(int $id): void
    {
        // No permitir que el admin se elimine a sí mismo
        if ($id === Auth::user()['id']) {
            Session::flash('error', 'No puedes desactivar tu propio usuario.');
            Response::redirect('/users');
        }

        $result = $this->userService->deactivate($id);

        if (!$result['success']) {
            Session::flash('error', $result['message'] ?? 'No se pudo desactivar el usuario.');
        } else {
            Session::flash('success', 'Usuario desactivado correctamente.');
        }

        Response::redirect('/users');
    }
}
