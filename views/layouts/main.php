<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'TechAsset Pro') ?> — TechAsset Pro</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS propio -->
    <link href="<?= APP_URL ?>/css/app.css" rel="stylesheet">
</head>
<body>

<!-- Sidebar -->
<?php require VIEWS_PATH . '/layouts/sidebar.php'; ?>

<!-- Contenido principal -->
<div class="main-content" id="mainContent">

    <!-- Topbar -->
    <?php require VIEWS_PATH . '/layouts/header.php'; ?>

    <!-- Página -->
    <div class="page-wrapper">
        <?php require VIEWS_PATH . '/' . ltrim($content, '/') . '.php'; ?>
    </div>

</div><!-- /main-content -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- JS propio -->
<script src="<?= APP_URL ?>/js/app.js"></script>

</body>
</html>
