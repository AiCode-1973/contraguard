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
            <?php
            $analise_pages = ['relatorios.php', 'dashboard_analise.php'];
            $analise_open  = in_array(basename($_SERVER['PHP_SELF']), $analise_pages);
            ?>
            <nav class="nav flex-column">
                <a href="#submenu-analise" class="nav-link nav-submenu-toggle <?php echo $analise_open ? '' : 'collapsed'; ?>"
                   data-bs-toggle="collapse" role="button" aria-expanded="<?php echo $analise_open ? 'true' : 'false'; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span class="nav-text">Análise <i class="fas fa-chevron-down submenu-chevron ms-auto"></i></span>
                </a>
                <div class="collapse <?php echo $analise_open ? 'show' : ''; ?>" id="submenu-analise">
                    <nav class="nav flex-column nav-submenu">
                        <a href="<?php echo APP_URL; ?>/pages/relatorios.php"
                           class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'relatorios.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-pie"></i> <span class="nav-text">Relatórios</span>
                        </a>
                        <a href="<?php echo APP_URL; ?>/pages/dashboard_analise.php"
                           class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard_analise.php' ? 'active' : ''; ?>">
                            <i class="fas fa-gauge-high"></i> <span class="nav-text">Dashboard Analítico</span>
                        </a>
                    </nav>
                </div>
            </nav>

            <?php if (isAdmin()): ?>
            <p class="nav-section-title">Administração</p>
            <nav class="nav flex-column">
                <a href="<?php echo APP_URL; ?>/pages/usuarios.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users-gear"></i> <span class="nav-text">Usuários</span>
                </a>
                <a href="<?php echo APP_URL; ?>/pages/categorias.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i> <span class="nav-text">Categorias</span>
                </a>
            </nav>
            <?php endif; ?>

            <div class="mt-auto sidebar-footer-form">
                <p class="nav-section-title">Filtros Rápidos</p>
                <form action="<?php echo APP_URL; ?>/index.php" method="GET" class="p-2">
                    <select name="categoria" class="form-select form-select-sm bg-light-navy text-white border-0 mb-2">
                        <option value="">Todas Categorias</option>
                        <?php
                        try {
                            $cats_sidebar = $pdo->query("SELECT nome FROM categorias ORDER BY nome ASC")->fetchAll(PDO::FETCH_COLUMN);
                        } catch (Exception $e) { $cats_sidebar = []; }
                        foreach ($cats_sidebar as $cs): ?>
                        <option value="<?php echo htmlspecialchars($cs); ?>"><?php echo htmlspecialchars($cs); ?></option>
                        <?php endforeach; ?>
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
                <div class="d-flex align-items-center gap-3">
                    <button class="sidebar-toggle-btn" id="sidebarToggle" title="Recolher/expandir menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2 class="mb-0 fw-bold">Olá, <?php echo explode(' ', $_SESSION['usuario_nome'])[0]; ?></h2>
                        <p class="text-secondary small mb-0"><?php echo formatarData(date('Y-m-d')); ?> | Status do Sistema: Online</p>
                    </div>
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
<script>
(function(){
    const sidebar = document.querySelector('.sidebar');
    const btn     = document.getElementById('sidebarToggle');
    if (!sidebar || !btn) return;
    if (localStorage.getItem('sidebarCollapsed') === '1') sidebar.classList.add('collapsed');
    btn.addEventListener('click', function(){
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
    });
})();
</script>
