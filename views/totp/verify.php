<?php /* views/totp/verify.php */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2FA — TechAsset Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); min-height: 100vh; display: flex; align-items: center; }
        .card { border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .code-input { font-size: 2rem; letter-spacing: 0.5rem; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card p-4">
                <div class="text-center mb-4">
                    <div class="bg-primary rounded-3 d-inline-flex p-3 mb-3">
                        <i class="fa-solid fa-shield-halved fa-2x text-white"></i>
                    </div>
                    <h1 class="h4 fw-bold">Verificación 2FA</h1>
                    <p class="text-muted small">Ingresa el código de tu app autenticadora</p>
                </div>

                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= APP_URL ?>/2fa/verify">
                    <?= \Core\Session::csrfField() ?>
                    <div class="mb-4">
                        <input type="text" name="code" 
                               class="form-control code-input"
                               maxlength="6" placeholder="000000"
                               autocomplete="one-time-code"
                               inputmode="numeric" pattern="[0-9]{6}"
                               required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-arrow-right me-2"></i>Verificar
                    </button>
                    <div class="text-center mt-3">
                        <a href="<?= APP_URL ?>/auth/login" class="text-muted small">
                            <i class="fa-solid fa-arrow-left me-1"></i>Volver al login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
