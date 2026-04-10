<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
verificarLogin();

// ── Migrações preventivas ─────────────────────────────────────────────────
$cols = $pdo->query("SHOW COLUMNS FROM contratos")->fetchAll(PDO::FETCH_COLUMN);
foreach (['valor','tipo_contrato','qtd_anos'] as $col) {
    if (!in_array($col, $cols)) {
        if ($col === 'valor')        $pdo->exec("ALTER TABLE contratos ADD COLUMN valor DECIMAL(15,2) DEFAULT NULL");
        if ($col === 'tipo_contrato') $pdo->exec("ALTER TABLE contratos ADD COLUMN tipo_contrato VARCHAR(20) DEFAULT NULL");
        if ($col === 'qtd_anos')     $pdo->exec("ALTER TABLE contratos ADD COLUMN qtd_anos INT DEFAULT NULL");
    }
}

// Filtro por usuário: cada um vê apenas os próprios registros
$uid = (int)$_SESSION['usuario_id'];
$uc  = " AND usuario_id = $uid";
$ug  = " AND usuario_id = $uid";

// ── KPIs gerais ───────────────────────────────────────────────────────────
$total_contratos  = (int)$pdo->query("SELECT COUNT(*) FROM contratos WHERE 1=1$uc")->fetchColumn();
$total_garantias  = (int)$pdo->query("SELECT COUNT(*) FROM garantias WHERE 1=1$ug")->fetchColumn();

$valor_total      = (float)$pdo->query("SELECT COALESCE(SUM(valor),0) FROM contratos WHERE 1=1$uc")->fetchColumn();
$valor_ativos     = (float)$pdo->query("SELECT COALESCE(SUM(valor),0) FROM contratos WHERE status='active'$uc")->fetchColumn();

$contratos_ativos    = (int)$pdo->query("SELECT COUNT(*) FROM contratos WHERE status='active'$uc")->fetchColumn();
$contratos_expiring  = (int)$pdo->query("SELECT COUNT(*) FROM contratos WHERE status='expiring'$uc")->fetchColumn();
$contratos_expired   = (int)$pdo->query("SELECT COUNT(*) FROM contratos WHERE status='expired'$uc")->fetchColumn();

$garantias_ativos    = (int)$pdo->query("SELECT COUNT(*) FROM garantias WHERE status='active'$ug")->fetchColumn();
$garantias_expiring  = (int)$pdo->query("SELECT COUNT(*) FROM garantias WHERE status='expiring'$ug")->fetchColumn();
$garantias_expired   = (int)$pdo->query("SELECT COUNT(*) FROM garantias WHERE status='expired'$ug")->fetchColumn();

$criticos_15d = (int)$pdo->query("
    SELECT
        (SELECT COUNT(*) FROM contratos WHERE data_fim >= CURDATE() AND data_fim <= DATE_ADD(CURDATE(), INTERVAL 15 DAY)$uc)
        + (SELECT COUNT(*) FROM garantias WHERE expira_garantia >= CURDATE() AND expira_garantia <= DATE_ADD(CURDATE(), INTERVAL 15 DAY)$ug)
    as total")->fetchColumn();

// ── Contratos por categoria ───────────────────────────────────────────────
$por_categoria = $pdo->query(
    "SELECT categoria, COUNT(*) as qtd, COALESCE(SUM(valor),0) as total_valor
     FROM contratos WHERE 1=1$uc GROUP BY categoria ORDER BY qtd DESC"
)->fetchAll();

// ── Contratos por tipo ────────────────────────────────────────────────────
$por_tipo = $pdo->query(
    "SELECT COALESCE(tipo_contrato,'Não definido') as tipo, COUNT(*) as qtd
     FROM contratos WHERE 1=1$uc GROUP BY tipo_contrato ORDER BY qtd DESC"
)->fetchAll();

// ── Vencimentos nos próximos 90 dias (mês a mês) ─────────────────────────
$venc_meses = $pdo->query("
    SELECT DATE_FORMAT(data_fim,'%Y-%m') as mes, COUNT(*) as qtd
    FROM contratos
    WHERE data_fim BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)$uc
    GROUP BY mes ORDER BY mes ASC
")->fetchAll();

// ── Top 5 contratos por valor ─────────────────────────────────────────────
$top_valores = $pdo->query(
    "SELECT nome, fornecedor, valor, status, data_fim
     FROM contratos WHERE valor IS NOT NULL$uc ORDER BY valor DESC LIMIT 5"
)->fetchAll();

// ── Últimos cadastros (contratos + garantias) ─────────────────────────────
$ultimos = $pdo->query("
    (SELECT 'Contrato' as tipo, nome, fornecedor, data_fim as data_ref, status FROM contratos WHERE 1=1$uc ORDER BY id DESC LIMIT 5)
    UNION
    (SELECT 'Garantia' as tipo, nome_equipamento as nome, fornecedor, expira_garantia as data_ref, status FROM garantias WHERE 1=1$ug ORDER BY id DESC LIMIT 5)
    ORDER BY data_ref DESC LIMIT 8
")->fetchAll();

include '../includes/header.php';
?>

<!-- KPI Cards -->
<div class="row g-4 mb-4 mt-2">
    <div class="col-6 col-md-3">
        <div class="stat-card h-100">
            <p class="stat-label">Contratos</p>
            <p class="stat-value"><?php echo $total_contratos; ?></p>
            <div class="small mt-2 text-secondary"><?php echo $contratos_ativos; ?> ativos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card h-100">
            <p class="stat-label">Garantias</p>
            <p class="stat-value"><?php echo $total_garantias; ?></p>
            <div class="small mt-2 text-secondary"><?php echo $garantias_ativos; ?> ativas</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card h-100">
            <p class="stat-label">Valor Total (contratos)</p>
            <p class="stat-value text-info" style="font-size:1.4rem;">R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></p>
            <div class="small mt-2 text-secondary">R$ <?php echo number_format($valor_ativos, 2, ',', '.'); ?> em ativos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card h-100">
            <p class="stat-label">Risco Crítico</p>
            <p class="stat-value text-danger"><?php echo $contratos_expired + $garantias_expired + $criticos_15d; ?></p>
            <div class="small mt-2">
                <?php if ($contratos_expired + $garantias_expired > 0): ?>
                    <span class="text-danger"><?php echo $contratos_expired + $garantias_expired; ?> vencido(s)</span>
                <?php endif; ?>
                <?php if ($criticos_15d > 0): ?>
                    <?php if ($contratos_expired + $garantias_expired > 0) echo ' · '; ?>
                    <span class="text-warning"><?php echo $criticos_15d; ?> em 15 dias</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Status dos Contratos -->
    <div class="col-md-4">
        <div class="card bg-navy border-0 rounded-4 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom border-secondary p-4 pb-3">
                <h6 class="m-0 fw-bold text-white"><i class="fas fa-file-invoice-dollar me-2 text-info"></i>Status dos Contratos</h6>
            </div>
            <div class="card-body p-4 text-white">
                <?php
                $total_c = $total_contratos ?: 1;
                $bars = [
                    ['label'=>'Ativos',           'val'=>$contratos_ativos,   'cls'=>'bg-success'],
                    ['label'=>'Vencendo em breve', 'val'=>$contratos_expiring, 'cls'=>'bg-warning'],
                    ['label'=>'Vencidos',          'val'=>$contratos_expired,  'cls'=>'bg-danger'],
                ];
                foreach ($bars as $b):
                    $pct = round(($b['val'] / $total_c) * 100);
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><?php echo $b['label']; ?></span>
                        <span class="fw-bold"><?php echo $b['val']; ?> <span class="text-secondary">(<?php echo $pct; ?>%)</span></span>
                    </div>
                    <div class="progress" style="height:8px; background:rgba(255,255,255,0.08);">
                        <div class="progress-bar <?php echo $b['cls']; ?>" style="width:<?php echo $pct; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Status das Garantias -->
    <div class="col-md-4">
        <div class="card bg-navy border-0 rounded-4 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom border-secondary p-4 pb-3">
                <h6 class="m-0 fw-bold text-white"><i class="fas fa-box-open me-2 text-info"></i>Status das Garantias</h6>
            </div>
            <div class="card-body p-4 text-white">
                <?php
                $total_g = $total_garantias ?: 1;
                $bars_g = [
                    ['label'=>'Ativas',            'val'=>$garantias_ativos,   'cls'=>'bg-success'],
                    ['label'=>'Vencendo em breve',  'val'=>$garantias_expiring, 'cls'=>'bg-warning'],
                    ['label'=>'Vencidas',           'val'=>$garantias_expired,  'cls'=>'bg-danger'],
                ];
                foreach ($bars_g as $b):
                    $pct = round(($b['val'] / $total_g) * 100);
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><?php echo $b['label']; ?></span>
                        <span class="fw-bold"><?php echo $b['val']; ?> <span class="text-secondary">(<?php echo $pct; ?>%)</span></span>
                    </div>
                    <div class="progress" style="height:8px; background:rgba(255,255,255,0.08);">
                        <div class="progress-bar <?php echo $b['cls']; ?>" style="width:<?php echo $pct; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Contratos por Tipo -->
    <div class="col-md-4">
        <div class="card bg-navy border-0 rounded-4 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom border-secondary p-4 pb-3">
                <h6 class="m-0 fw-bold text-white"><i class="fas fa-rotate me-2 text-info"></i>Contratos por Tipo</h6>
            </div>
            <div class="card-body p-4 text-white">
                <?php if (empty($por_tipo)): ?>
                    <p class="text-secondary small">Nenhum tipo cadastrado.</p>
                <?php else: ?>
                    <?php foreach ($por_tipo as $t):
                        $pct = round(($t['qtd'] / $total_c) * 100);
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><?php echo htmlspecialchars($t['tipo']); ?></span>
                            <span class="fw-bold"><?php echo $t['qtd']; ?> <span class="text-secondary">(<?php echo $pct; ?>%)</span></span>
                        </div>
                        <div class="progress" style="height:8px; background:rgba(255,255,255,0.08);">
                            <div class="progress-bar bg-info" style="width:<?php echo $pct; ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Contratos por Categoria -->
    <div class="col-md-6">
        <div class="card bg-navy border-0 rounded-4 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom border-secondary p-4 pb-3">
                <h6 class="m-0 fw-bold text-white"><i class="fas fa-tags me-2 text-info"></i>Valor por Categoria</h6>
            </div>
            <div class="card-body p-0 text-white">
                <table class="table table-dark-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Categoria</th>
                            <th class="text-center">Qtd</th>
                            <th class="pe-4 text-end">Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($por_categoria)): ?>
                        <tr><td colspan="3" class="text-center text-secondary p-4">Nenhum dado.</td></tr>
                        <?php else: ?>
                        <?php foreach ($por_categoria as $cat): ?>
                        <tr>
                            <td class="ps-4"><i class="fas fa-circle me-2 text-info" style="font-size:.5rem;"></i><?php echo htmlspecialchars($cat['categoria'] ?: '—'); ?></td>
                            <td class="text-center"><?php echo $cat['qtd']; ?></td>
                            <td class="pe-4 text-end fw-bold"><?php echo $cat['total_valor'] > 0 ? 'R$ '.number_format($cat['total_valor'],2,',','.') : '<span class="text-secondary">—</span>'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top 5 por Valor -->
    <div class="col-md-6">
        <div class="card bg-navy border-0 rounded-4 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom border-secondary p-4 pb-3">
                <h6 class="m-0 fw-bold text-white"><i class="fas fa-trophy me-2 text-warning"></i>Top 5 Contratos por Valor</h6>
            </div>
            <div class="card-body p-0 text-white">
                <table class="table table-dark-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Contrato</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_valores)): ?>
                        <tr><td colspan="3" class="text-center text-secondary p-4">Nenhum valor cadastrado.</td></tr>
                        <?php else: ?>
                        <?php foreach ($top_valores as $tv): ?>
                        <tr>
                            <td class="ps-4">
                                <strong><?php echo htmlspecialchars($tv['nome']); ?></strong>
                                <div class="small text-secondary"><?php echo htmlspecialchars($tv['fornecedor']); ?></div>
                            </td>
                            <td><span class="badge badge-<?php echo $tv['status']; ?>"><?php echo getStatusLabel($tv['status']); ?></span></td>
                            <td class="pe-4 text-end fw-bold text-info">R$ <?php echo number_format((float)$tv['valor'],2,',','.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Vencimentos próximos 90 dias + Últimos cadastros -->
<div class="row g-4 mb-4">
    <div class="col-md-5">
        <div class="card bg-navy border-0 rounded-4 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom border-secondary p-4 pb-3">
                <h6 class="m-0 fw-bold text-white"><i class="fas fa-calendar-days me-2 text-info"></i>Vencimentos — Próximos 90 dias</h6>
            </div>
            <div class="card-body p-4 text-white">
                <?php if (empty($venc_meses)): ?>
                    <p class="text-secondary small">Nenhum vencimento nos próximos 90 dias.</p>
                <?php else: ?>
                    <?php
                    $max_qtd = max(array_column($venc_meses, 'qtd')) ?: 1;
                    foreach ($venc_meses as $vm):
                        $pct = round(($vm['qtd'] / $max_qtd) * 100);
                        $mes_fmt = date('M/Y', strtotime($vm['mes'].'-01'));
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><?php echo $mes_fmt; ?></span>
                            <span class="fw-bold text-warning"><?php echo $vm['qtd']; ?> contrato(s)</span>
                        </div>
                        <div class="progress" style="height:8px; background:rgba(255,255,255,0.08);">
                            <div class="progress-bar bg-warning" style="width:<?php echo $pct; ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card bg-navy border-0 rounded-4 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom border-secondary p-4 pb-3">
                <h6 class="m-0 fw-bold text-white"><i class="fas fa-clock-rotate-left me-2 text-info"></i>Últimos Cadastros</h6>
            </div>
            <div class="card-body p-0 text-white">
                <table class="table table-dark-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Tipo</th>
                            <th>Nome</th>
                            <th>Fornecedor</th>
                            <th class="pe-4">Vencimento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos as $u): ?>
                        <tr>
                            <td class="ps-4"><span class="badge bg-secondary"><?php echo $u['tipo']; ?></span></td>
                            <td><?php echo htmlspecialchars($u['nome']); ?></td>
                            <td class="text-secondary"><?php echo htmlspecialchars($u['fornecedor']); ?></td>
                            <td class="pe-4"><?php echo formatarData($u['data_ref']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
