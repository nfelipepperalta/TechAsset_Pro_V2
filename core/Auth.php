<?php
// ============================================================
//  core/Auth.php
//  Control de acceso basado en roles — RBAC
//  Roles: usuario < auditor < ti < admin
// ============================================================

namespace Core;

class Auth
{
    // Jerarquía de roles (índice mayor = más privilegios)
    private const ROLE_HIERARCHY = [
        'usuario'  => 0,
        'auditor'  => 1,
        'ti'       => 2,
        'admin'    => 3,
    ];

    // Verificar si hay sesión activa, si no redirigir al login
    public static function check(): void
    {
        if (!Session::isLoggedIn()) {
            Session::flash('error', 'Debes iniciar sesión para acceder.');
            Response::redirect('/auth/login');
        }
    }

    // Verificar que el usuario tenga al menos el rol indicado
    public static function requireRole(string $minRole): void
    {
        self::check();

        $user     = Session::getUser();
        $userRole = $user['rol'] ?? 'usuario';

        if (!self::hasRole($userRole, $minRole)) {
            Session::flash('error', 'No tienes permisos para acceder a esta sección.');
            Response::redirect('/dashboard');
        }
    }

    // Verificar si un rol tiene al menos el nivel requerido
    public static function hasRole(string $userRole, string $minRole): bool
    {
        $userLevel = self::ROLE_HIERARCHY[$userRole] ?? 0;
        $minLevel  = self::ROLE_HIERARCHY[$minRole]  ?? 0;
        return $userLevel >= $minLevel;
    }

    // Obtener el usuario autenticado actual
    public static function user(): ?array
    {
        return Session::getUser();
    }

    // Obtener solo el rol del usuario actual
    public static function role(): string
    {
        $user = Session::getUser();
        return $user['rol'] ?? 'usuario';
    }

    // Verificar si el usuario actual es admin
    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    // Verificar si el usuario puede editar (ti o admin)
    public static function canEdit(): bool
    {
        return self::hasRole(self::role(), 'ti');
    }

    // Verificar si el usuario puede ver reportes (auditor o superior)
    public static function canAudit(): bool
    {
        return self::hasRole(self::role(), 'auditor');
    }

    // Guardar usuario en sesión tras login exitoso
    public static function login(array $user): void
    {
        // Solo guardar datos necesarios, nunca el password_hash
        Session::setUser([
            'id'            => $user['id'],
            'nombre'        => $user['nombre'],
            'email'         => $user['email'],
            'rol'           => $user['rol'],
            'department_id' => $user['department_id'],
        ]);
    }

    // Cerrar sesión
    public static function logout(): void
    {
        Session::destroy();
        Response::redirect('/auth/login');
    }
}
