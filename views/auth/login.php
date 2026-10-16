<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — TechAsset Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/css/app.css" rel="stylesheet">
</head>
<body class="login-body">

<div class="login-wrapper">

    <div class="login-card">

        <!-- Logo -->
        <div class="login-brand">
            <div class="login-icon">
                <i class="fa-solid fa-microchip"></i>
            </div>
            <h1 class="login-title">TechAsset <span>Pro</span></h1>
            <p class="login-subtitle">Sistema de Inventario Tecnológico</p>
        </div>

        <!-- Error flash -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <form method="POST" action="<?= APP_URL ?>/auth/login" novalidate>

            <div class="form-group mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="usuario@empresa.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="form-group mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="••••••••"
                        required
                    >
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fa-solid fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-login">
                <i class="fa-solid fa-right-to-bracket me-2"></i>
                Ingresar al sistema
            </button>

        </form>

    </div>

    <p class="login-footer">
        &copy; <?= date('Y') ?> TechAsset Pro · Feria de Tecnología 2025
    </p>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle mostrar/ocultar contraseña
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input   = document.getElementById('password');
        const icon    = document.getElementById('eyeIcon');
        const visible = input.type === 'text';
        input.type    = visible ? 'password' : 'text';
        icon.classList.toggle('fa-eye',        visible);
        icon.classList.toggle('fa-eye-slash', !visible);
    });
</script>
</body>
</html>
