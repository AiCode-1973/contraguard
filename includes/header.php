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
    <title><?php echo APP_NAME; ?> | Smart Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="app-container">
        <?php if (isset($_SESSION['usuario_id'])): ?>
        <aside class="sidebar">
            <div class="logo-container">
                <i class="fas fa-shield-halved"></i>
                <span class="logo-text">ContraGuard</span>
            </div>
            
            <p class="nav-section-title">Principal</p>
            <nav class="nav flex-column">
                <a href="<?php echo APP_URL; ?>/index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-table-columns"></i> <span class="nav-text">Dashboard</span>
                </a>
                <a href="<?php echo APP_URL; ?>/pages/contratos.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contratos.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar"></i> <span class="nav-text">Contratos</span>
                </a>
                <a href="<?php echo APP_URL; ?>/pages/garantias.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'garantias.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box-open"></i> <span class="nav-text">Garantias</span>
                </a>
            </nav>

            <p class="nav-section-title">Análise</p>
            <nav class="nav flex-column">
                <a href="<?php echo APP_URL; ?>/pages/relatorios.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'relatorios.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i> <span class="nav-text">Relatórios</span>
                </a>
            </nav>

            <div class="mt-auto">
                <p class="nav-section-title">Filtros Rápidos</p>
                <form action="<?php echo APP_URL; ?>/index.php" method="GET" class="p-2">
                    <select name="categoria" class="form-select form-select-sm bg-light-navy text-white border-0 mb-2">
                        <option value="">Todas Categorias</option>
                        <option value="Software">Software</option>
                        <option value="Hardware">Hardware</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-info w-100 fw-bold">Aplicar</button>
                </form>
                
                <hr class="text-secondary mx-2">
                <a href="<?php echo APP_URL; ?>/logout.php" class="nav-link text-danger">
                    <i class="fas fa-power-off"></i> <span class="nav-text">Sair</span>
                </a>
            </div>
        </aside>
        <?php endif; ?>

        <main class="main-content <?php echo !isset($_SESSION['usuario_id']) ? 'w-100 p-0 m-0' : ''; ?>">
            <?php if (isset($_SESSION['usuario_id'])): ?>
            <header class="top-header">
                <div>
                    <h2 class="mb-0 fw-bold">Olá, <?php echo explode(' ', $_SESSION['usuario_nome'])[0]; ?></h2>
                    <p class="text-secondary small"><?php echo formatarData(date('Y-m-d')); ?> | Status do Sistema: Online</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Pesquisar em tudo...">
                    </div>
                    <div class="position-relative">
                        <i class="fas fa-bell fs-5 text-secondary cursor-pointer"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.5rem;">3</span>
                    </div>
                    <div class="avatar bg-accent-blue text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                        <?php echo strtoupper(substr($_SESSION['usuario_nome'], 0, 2)); ?>
                    </div>
                </div>
            </header>
            <?php endif; ?>
