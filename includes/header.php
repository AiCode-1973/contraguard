<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> | Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <?php if (isset($_SESSION['usuario_id'])): ?>
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo-container p-3">
                <i class="fas fa-shield-alt"></i>
                <span class="logo-text ms-2">ContraGuard</span>
            </div>
            
            <nav class="nav flex-column p-2">
                <a href="<?php echo APP_URL; ?>/index.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large me-2"></i> Visão Geral
                </a>
                <a href="<?php echo APP_URL; ?>/pages/contratos.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'contratos.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-contract me-2"></i> Contratos
                </a>
                <a href="<?php echo APP_URL; ?>/pages/garantias.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'garantias.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tools me-2"></i> Garantias
                </a>
                <?php if (isAdmin()): ?>
                <a href="<?php echo APP_URL; ?>/pages/relatorios.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'relatorios.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-pdf me-2"></i> Relatórios
                </a>
                <?php endif; ?>
                <hr class="text-secondary">
                <a href="<?php echo APP_URL; ?>/logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Sair
                </a>
            </nav>
        </aside>
        <?php endif; ?>

        <main class="main-content <?php echo !isset($_SESSION['usuario_id']) ? 'w-100 p-0' : ''; ?>">
