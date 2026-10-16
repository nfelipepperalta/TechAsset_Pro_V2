<?php use Core\Auth; ?>

<header class="topbar">

    <!-- Toggle sidebar (móvil) -->
    <button class="sidebar-toggle" id="sidebarToggle" type="button">
        <i class="fa-solid fa-bars"></i>
    </button>

    <!-- Título de la página -->
    <h1 class="page-title"><?= htmlspecialchars($title ?? '') ?></h1>

    <!-- Acciones topbar -->
    <div class="topbar-actions">

        <!-- Alertas rápidas -->
        <a href="<?= APP_URL ?>/alerts" class="topbar-btn" title="Alertas">
            <i class="fa-solid fa-bell"></i>
            <?php if (!empty($alertCount) && $alertCount > 0): ?>
                <span class="topbar-badge"><?= $alertCount ?></span>
            <?php endif; ?>
        </a>

        <!-- Menú usuario -->
        <div class="dropdown">
            <button class="topbar-user dropdown-toggle" data-bs-toggle="dropdown">
                <div class="topbar-avatar">
                    <?= strtoupper(substr(Auth::user()['nombre'] ?? 'U', 0, 1)) ?>
                </div>
                <span class="d-none d-md-inline">
                    <?= htmlspecialchars(Auth::user()['nombre'] ?? '') ?>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <span class="dropdown-item-text small text-muted">
                        <?= htmlspecialchars(Auth::user()['email'] ?? '') ?>
                    </span>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="<?= APP_URL ?>/2fa/setup">
                        <i class="fa-solid fa-shield-halved me-2"></i><?= Auth::user()['totp_enabled'] ?? 0 ? 'Gestionar 2FA' : 'Activar 2FA' ?>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?= APP_URL ?>/auth/logout">
                        <i class="fa-solid fa-right-from-bracket me-2"></i>Cerrar sesión
                    </a>
                </li>
            </ul>
        </div>

    </div>
</header>
