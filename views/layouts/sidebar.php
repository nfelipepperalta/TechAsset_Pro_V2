<?php
use Core\Auth;

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function isActive(string $path, string $current): string {
    return str_starts_with($current, $path) ? 'active' : '';
}
?>

<nav class="sidebar" id="sidebar">

    <!-- Logo -->
    <div class="sidebar-brand">
        <a href="<?= APP_URL ?>/dashboard" class="brand-link">
            <div class="brand-icon">
                <i class="fa-solid fa-microchip"></i>
            </div>
            <div class="brand-text">
                <span class="brand-name">TechAsset</span>
                <span class="brand-pro">PRO</span>
            </div>
        </a>
    </div>

    <!-- Menú -->
    <ul class="sidebar-menu">

        <li class="menu-item <?= isActive('/dashboard', $currentPath) ?>">
            <a href="<?= APP_URL ?>/dashboard">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="menu-item <?= isActive('/assets', $currentPath) ?>">
            <a href="<?= APP_URL ?>/assets">
                <i class="fa-solid fa-server"></i>
                <span>Inventario</span>
            </a>
        </li>

        <li class="menu-item <?= isActive('/maintenance', $currentPath) ?>">
            <a href="<?= APP_URL ?>/maintenance">
                <i class="fa-solid fa-screwdriver-wrench"></i>
                <span>Mantenimientos</span>
            </a>
        </li>

        <li class="menu-item <?= isActive('/alerts', $currentPath) ?>">
            <a href="<?= APP_URL ?>/alerts">
                <i class="fa-solid fa-bell"></i>
                <span>Alertas</span>
            </a>
        </li>

        <?php if (Auth::canAudit()): ?>
        <li class="menu-item <?= isActive('/reports', $currentPath) ?>">
            <a href="<?= APP_URL ?>/reports">
                <i class="fa-solid fa-chart-bar"></i>
                <span>Reportes</span>
            </a>
        </li>
        <li class="menu-item <?= isActive('/ia', $currentPath) ?>">
            <a href="<?= APP_URL ?>/ia">
                <i class="fa-solid fa-brain"></i>
                <span>IA Predictiva</span>
            </a>
        </li>
        <li class="menu-item <?= isActive('/scanner', $currentPath) ?>">
            <a href="<?= APP_URL ?>/scanner">
                <i class="fa-solid fa-radar"></i>
                <span>Escáner de Red</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (Auth::isAdmin()): ?>
        <li class="menu-divider"><span>Administración</span></li>
        <li class="menu-item <?= isActive('/users', $currentPath) ?>">
            <a href="<?= APP_URL ?>/users">
                <i class="fa-solid fa-users"></i>
                <span>Usuarios</span>
            </a>
        </li>
        <?php endif; ?>

    </ul>

    <!-- Usuario en pie del sidebar -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr(Auth::user()['nombre'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-details">
                <span class="user-name"><?= htmlspecialchars(Auth::user()['nombre'] ?? '') ?></span>
                <span class="user-role"><?= ucfirst(Auth::role()) ?></span>
            </div>
        </div>
        <a href="<?= APP_URL ?>/auth/logout" class="logout-btn" title="Cerrar sesión">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </div>

</nav>
