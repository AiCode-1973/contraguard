<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
verificarLogin();

// Lógica de Alertas
function atualizarAlertas($pdo) {
    $pdo->exec("UPDATE contratos SET status = 'expired' WHERE data_fim < CURDATE()");
    $pdo->exec("UPDATE contratos SET status = 'expiring' WHERE data_fim >= CURDATE() AND data_fim <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)");
    $pdo->exec("UPDATE contratos SET status = 'active' WHERE data_fim > DATE_ADD(CURDATE(), INTERVAL 60 DAY)");

    $pdo->exec("UPDATE garantias SET status = 'expired' WHERE expira_garantia < CURDATE()");
    $pdo->exec("UPDATE garantias SET status = 'expiring' WHERE expira_garantia >= CURDATE() AND expira_garantia <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)");
    $pdo->exec("UPDATE garantias SET status = 'active' WHERE expira_garantia > DATE_ADD(CURDATE(), INTERVAL 60 DAY)");
}

atualizarAlertas($pdo);

// Migrações automáticas (garante colunas antes de qualquer query)
$colunas_idx = $pdo->query("SHOW COLUMNS FROM contratos")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('tipo_contrato', $colunas_idx)) {
    $pdo->exec("ALTER TABLE contratos ADD COLUMN tipo_contrato VARCHAR(20) DEFAULT NULL");
}
if (!in_array('qtd_anos', $colunas_idx)) {
    $pdo->exec("ALTER TABLE contratos ADD COLUMN qtd_anos INT DEFAULT NULL");
}
if (!in_array('valor', $colunas_idx)) {
    $pdo->exec("ALTER TABLE contratos ADD COLUMN valor DECIMAL(15,2) DEFAULT NULL");
}

// Migração: registros sem usuario_id herdam o primeiro admin
$pdo->exec("UPDATE contratos SET usuario_id = (SELECT id FROM usuarios WHERE nivel='admin' ORDER BY id LIMIT 1) WHERE usuario_id IS NULL");
$pdo->exec("UPDATE garantias SET usuario_id = (SELECT id FROM usuarios WHERE nivel='admin' ORDER BY id LIMIT 1) WHERE usuario_id IS NULL");

// Filtro por setor: vê registros de todos os usuários do mesmo setor
$uid = (int)$_SESSION['usuario_id'];
$sid = (int)($_SESSION['usuario_setor_id'] ?? 0);
$uid_filter = $sid > 0
    ? "IN (SELECT id FROM usuarios WHERE setor_id = $sid)"
    : "= $uid";
$uc = " AND usuario_id $uid_filter";
$ug = " AND usuario_id $uid_filter";

// Filtros
$cat_filter = $_GET['categoria'] ?? '';
$busca      = trim($_GET['busca'] ?? '');
$where = " WHERE 1=1$uc";
$params = [];
if ($cat_filter) {
    $where .= " AND categoria = ?";
    $params[] = $cat_filter;
}
if ($busca) {
    $where .= " AND (nome LIKE ? OR fornecedor LIKE ? OR responsavel LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

// Métricas (ignoram filtro de busca/categoria, refletem totais do usuário)
$total_contratos = $pdo->query("SELECT COUNT(*) FROM contratos WHERE 1=1$uc")->fetchColumn();
$vencendo_breve = $pdo->query("SELECT (SELECT COUNT(*) FROM contratos WHERE status = 'expiring'$uc) + (SELECT COUNT(*) FROM garantias WHERE status = 'expiring'$ug) as total")->fetchColumn();
$vencidos = $pdo->query("SELECT (SELECT COUNT(*) FROM contratos WHERE status = 'expired'$uc) + (SELECT COUNT(*) FROM garantias WHERE status = 'expired'$ug) as total")->fetchColumn();
$criticos_15d = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM contratos WHERE data_fim >= CURDATE() AND data_fim <= DATE_ADD(CURDATE(), INTERVAL 15 DAY)$uc)
        + (SELECT COUNT(*) FROM garantias WHERE expira_garantia >= CURDATE() AND expira_garantia <= DATE_ADD(CURDATE(), INTERVAL 15 DAY)$ug)
    as total
")->fetchColumn();

// Busca em garantias: nome_equipamento, fornecedor, responsavel
$where_g_busca = " WHERE 1=1$ug";
$params_g = [];
if ($busca) {
    $where_g_busca .= " AND (nome_equipamento LIKE ? OR fornecedor LIKE ? OR responsavel LIKE ?)";
    $params_g[] = "%$busca%";
    $params_g[] = "%$busca%";
    $params_g[] = "%$busca%";
}

// Buscar Itens para o Grid
$query = "
    (SELECT 'Contrato' as tipo, nome, fornecedor, data_fim as data, status, responsavel, categoria, tipo_contrato, qtd_anos FROM contratos $where)
    UNION
    (SELECT 'Garantia' as tipo, nome_equipamento as nome, fornecedor, expira_garantia as data, status, responsavel, 'Hardware' as categoria, tipo_garantia as tipo_contrato, qtd_anos FROM garantias $where_g_busca)
    ORDER BY data ASC
";
$items = $pdo->prepare($query);
$items->execute(array_merge($params, $params_g));
$listagem = $items->fetchAll();

include 'includes/header.php';
?>

<!-- Statistics -->
<section class="stats-grid mb-5">
    <div class="stat-card">
        <p class="stat-label">Total Gerenciado</p>
        <p class="stat-value"><?php echo $total_contratos + $pdo->query("SELECT COUNT(*) FROM garantias WHERE 1=1$ug")->fetchColumn(); ?></p>
        <div class="text-info small mt-3 fw-bold"><i class="fas fa-arrow-up me-1"></i> +2 este mês</div>
    </div>
    <div class="stat-card">
        <p class="stat-label">Riscos Críticos</p>
        <p class="stat-value text-danger"><?php echo $vencidos + $criticos_15d; ?></p>
        <div class="text-secondary small mt-3">
            <?php if ($vencidos > 0): ?>
                <span class="text-danger fw-bold"><?php echo $vencidos; ?> vencido<?php echo $vencidos > 1 ? 's' : ''; ?></span>
                <?php if ($criticos_15d > 0): ?> &nbsp;·&nbsp; <?php endif; ?>
            <?php endif; ?>
            <?php if ($criticos_15d > 0): ?>
                <span class="text-warning fw-bold"><?php echo $criticos_15d; ?> vence<?php echo $criticos_15d > 1 ? 'm' : ''; ?> em até 15 dias</span>
            <?php endif; ?>
            <?php if ($vencidos == 0 && $criticos_15d == 0): ?>
                <span>Nenhum risco crítico</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card">
        <p class="stat-label">Em Renovação</p>
        <p class="stat-value text-warning"><?php echo $vencendo_breve; ?></p>
        <div class="text-secondary small mt-3">Próximos 60 dias</div>
    </div>
</section>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Alertas e Prazos Recentes</h4>
        <?php if ($busca): ?>
        <div class="small mt-1">
            <span class="text-secondary">Resultado para: </span>
            <span class="badge bg-info text-dark fw-semibold"><?php echo htmlspecialchars($busca); ?></span>
            <a href="index.php<?php echo $cat_filter ? '?categoria='.urlencode($cat_filter) : ''; ?>" class="ms-2 text-secondary small"><i class="fas fa-xmark me-1"></i>Limpar</a>
        </div>
        <?php endif; ?>
    </div>
    <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            Ordenar por
        </button>
        <ul class="dropdown-menu dropdown-menu-dark">
            <li><a class="dropdown-item" href="#">Data de Expiração</a></li>
            <li><a class="dropdown-item" href="#">Prioridade</a></li>
        </ul>
    </div>
</div>

<!-- Grid de Alertas -->
<div class="alert-grid">
    <?php if (empty($listagem)): ?>
        <div class="col-12 text-center p-5 card">
            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
            <p class="text-secondary">Nenhum item encontrado com os filtros aplicados.</p>
        </div>
    <?php else: ?>
        <?php foreach($listagem as $item): 
            $dias = calcularDiasRestantes($item['data']);
            $status_class = $item['status'];
            $color = ($status_class == 'active') ? 'var(--status-active)' : (($status_class == 'expiring') ? 'var(--status-expiring)' : 'var(--status-expired)');
            
            // Lógica de progresso (exemplo: 1 ano de ciclo)
            $progresso = min(100, max(0, 100 - ($dias / 3.65))); 
            if ($dias < 0) $progresso = 100;
        ?>
        <div class="alert-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="card-tag"><?php echo $item['tipo']; ?></span>
                <span class="badge rounded-pill <?php echo ($status_class == 'active' ? 'bg-success' : ($status_class == 'expiring' ? 'bg-warning text-dark' : 'bg-danger')); ?>" style="font-size: 0.65rem;">
                    <?php echo getStatusLabel($item['status']); ?>
                </span>
            </div>
            
            <h5 class="card-title text-truncate"><?php echo $item['nome']; ?></h5>
            <p class="card-subtitle"><?php echo $item['fornecedor']; ?> • <?php echo $item['responsavel']; ?></p>
            <?php if (!empty($item['tipo_contrato'])): ?>
            <span class="badge bg-info text-dark mt-1" style="font-size:0.65rem;"><i class="fas fa-rotate me-1"></i><?php echo formatarTipoContrato($item['tipo_contrato'], $item['qtd_anos']); ?></span>
            <?php endif; ?>
            
            <div class="mt-4">
                <div class="card-progress-label">
                    <span><?php echo $dias < 0 ? abs($dias).' dias vencidos' : $dias.' dias restantes'; ?></span>
                    <span><?php echo round($progresso); ?>%</span>
                </div>
                <div class="card-progress-bg">
                    <div class="card-progress-fill" style="width: <?php echo $progresso; ?>%; background-color: <?php echo $color; ?>;"></div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-between align-items-center">
                <div class="small text-secondary">
                    <i class="far fa-calendar-alt me-1"></i> <?php echo formatarData($item['data']); ?>
                </div>
                <a href="pages/contratos.php" class="btn btn-sm btn-link text-accent p-0 text-decoration-none">Ver detalhes <i class="fas fa-chevron-right ms-1 small"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
