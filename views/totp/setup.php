<?php /* views/totp/setup.php */ ?>

<div class="row justify-content-center">
<div class="col-lg-6">

<?php if (!empty($success)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title mb-0">
            <i class="fa-solid fa-shield-halved me-2"></i>Configurar Autenticación de Dos Factores
        </h2>
    </div>
    <div class="card-body">
        <p class="text-muted mb-4">
            Escanea el código QR con tu app autenticadora 
            (<strong>Google Authenticator</strong>, <strong>Microsoft Authenticator</strong> o <strong>Authy</strong>).
        </p>

        <!-- QR Code -->
        <div class="text-center mb-4">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($qr_url) ?>" 
                 alt="QR 2FA" class="border rounded p-2">
        </div>

        <!-- Secret manual -->
        <div class="alert alert-light border mb-4">
            <p class="small text-muted mb-1">¿No puedes escanear el QR? Ingresa este código manualmente:</p>
            <code class="fs-6 user-select-all"><?= htmlspecialchars($secret) ?></code>
        </div>

        <!-- Formulario verificación -->
        <form method="POST" action="<?= APP_URL ?>/2fa/setup">
            <?= \Core\Session::csrfField() ?>
            <div class="mb-3">
                <label class="form-label required">Código de verificación</label>
                <input type="text" name="code" class="form-control form-control-lg text-center"
                       maxlength="6" placeholder="000000" autocomplete="one-time-code"
                       inputmode="numeric" pattern="[0-9]{6}" required autofocus>
                <div class="form-text">Ingresa el código de 6 dígitos de tu app autenticadora.</div>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fa-solid fa-check me-2"></i>Activar 2FA
            </button>
        </form>
    </div>
</div>

</div>
</div>
