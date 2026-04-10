<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
verificarLogin();

$status    = $_GET['status'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$periodo   = $_GET['periodo'] ?? '';

// Filtro por usuário: cada um vê apenas os próprios registros
$uid = (int)$_SESSION['usuario_id'];
$uc  = " AND usuario_id = $uid";
$ug  = " AND usuario_id = $uid";

// Carregar categorias do banco
$pdo->exec("CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao VARCHAR(255) DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if (isAdmin()) {
    $categorias_list = $pdo->query("SELECT nome FROM categorias WHERE usuario_id = " . (int)$_SESSION['usuario_id'] . " ORDER BY nome ASC")->fetchAll(PDO::FETCH_COLUMN);
} else {
    $cats_r = $pdo->prepare("SELECT nome FROM categorias WHERE usuario_id = ? ORDER BY nome ASC");
    $cats_r->execute([(int)$_SESSION['usuario_id']]);
    $categorias_list = $cats_r->fetchAll(PDO::FETCH_COLUMN);
}

$query_contratos = "SELECT 'Contrato' as tipo, nome, fornecedor, data_fim as vencimento, status, valor, tipo_contrato as tipo_periodo, qtd_anos FROM contratos WHERE 1=1$uc";
$params_contratos = [];

if ($status) {
    $query_contratos .= " AND status = ?";
    $params_contratos[] = $status;
}

if ($categoria) {
    $query_contratos .= " AND categoria = ?";
    $params_contratos[] = $categoria;
}

$query_garantias = "SELECT 'Garantia' as tipo, nome_equipamento as nome, fornecedor, expira_garantia as vencimento, status, NULL as valor, tipo_garantia as tipo_periodo, qtd_anos FROM garantias WHERE 1=1$ug";
$params_garantias = [];

if ($status) {
    $query_garantias .= " AND status = ?";
    $params_garantias[] = $status;
}

// Em garantias não temos categoria no mesmo campo, mas podemos adicionar se necessário. 
// Para manter a união simples, filtramos apenas por status se fornecido.

$query_final = "($query_contratos) UNION ($query_garantias) ORDER BY vencimento ASC";
$params_final = array_merge($params_contratos, $params_garantias);

$relatorio = $pdo->prepare($query_final);
$relatorio->execute($params_final);
$dados = $relatorio->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Relatórios do Sistema</h2>
    <button class="btn btn-danger" onclick="window.print()">
        <i class="fas fa-file-pdf me-2"></i> Exportar / Imprimir
    </button>
</div>

<!-- Cabeçalho exclusivo para impressao -->
<div id="print-header" style="display:none;">
    <div style="display:flex; align-items:center; justify-content:space-between; border-bottom:2px solid #000; padding-bottom:12px; margin-bottom:16px;">
        <img src="<?php echo APP_URL; ?>/images/Logo_hse.png" alt="Logo" style="max-height:70px; max-width:200px;">
        <div style="text-align:right;">
            <div style="font-size:1.3rem; font-weight:bold;">Relatório de Contratos e Garantias</div>
            <div style="font-size:0.85rem; color:#555;">Emitido em: <?php echo formatarData(date('Y-m-d')); ?> — <?php echo APP_NAME; ?></div>
            <?php if ($status || $categoria): ?>
            <div style="font-size:0.8rem; color:#777;">
                Filtros:
                <?php if ($status) echo 'Status: ' . getStatusLabel($status); ?>
                <?php if ($status && $categoria) echo ' | '; ?>
                <?php if ($categoria) echo 'Categoria: ' . htmlspecialchars($categoria); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!--<h4 class="fw-bold mb-4">Relatórios de Atividade</h4>-->
<div class="card bg-navy border-0 rounded-4 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-uppercase fw-bold opacity-75">Status</label>
                <select name="status" class="form-select border-secondary">
                    <option value="">Todos os Status</option>
                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Ativos</option>
                    <option value="expiring" <?php echo $status == 'expiring' ? 'selected' : ''; ?>>Vencendo em breve</option>
                    <option value="expired" <?php echo $status == 'expired' ? 'selected' : ''; ?>>Expirados</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-uppercase fw-bold opacity-75">Categoria</label>
                <select name="categoria" class="form-select border-secondary">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($categorias_list as $cat_nome): ?>
                    <option value="<?php echo htmlspecialchars($cat_nome); ?>" <?php echo $categoria == $cat_nome ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat_nome); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-info w-100 fw-bold px-4 py-2 mt-2">
                    <i class="fas fa-filter me-2"></i> Filtrar agora
                </button>
            </div>
            <div class="col-md-3">
                <button type="button" onclick="window.print()" class="btn btn-outline-info w-100 fw-bold px-4 py-2 mt-2">
                    <i class="fas fa-print me-2"></i> Impressão / PDF
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card bg-navy border-0 rounded-4 shadow-sm overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-dark-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tipo</th>
                        <th>Nome / Item</th>
                        <th>Fornecedor</th>
                        <th>Valor</th>                        <th>Periodicidade</th>                        <th>Vencimento</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($dados as $d): ?>
                    <tr>
                        <td class="ps-3"><span class="badge bg-secondary"><?php echo $d['tipo']; ?></span></td>
                        <td><strong><?php echo htmlspecialchars($d['nome']); ?></strong></td>
                        <td><?php echo htmlspecialchars($d['fornecedor']); ?></td>
                        <td><?php echo !empty($d['valor']) ? 'R$ ' . number_format((float)$d['valor'], 2, ',', '.') : '<span class="text-secondary">—</span>'; ?></td>
                        <td><?php echo !empty($d['tipo_periodo']) ? htmlspecialchars(formatarTipoContrato($d['tipo_periodo'], $d['qtd_anos'])) : '<span class="text-secondary">—</span>'; ?></td>
                        <td><?php echo formatarData($d['vencimento']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $d['status']; ?>">
                                <?php echo getStatusLabel($d['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style type="text/css">
@media print {
    .sidebar, .btn, form, .badge-info, .top-header, .dropdown { display: none !important; }
    .main-content { margin-left: 0 !important; width: 100% !important; padding: 16px !important; }
    .card { border: 1px solid #ccc !important; box-shadow: none !important; }
    body { background: white !important; color: black !important; }
    .text-white, h2, h4, strong { color: black !important; }
    .bg-navy, .bg-dark, .card { background: white !important; }
    .table { color: black !important; border-collapse: collapse !important; width: 100% !important; }
    .table th, .table td { border: 1px solid #ccc !important; padding: 6px 10px !important; color: black !important; }
    .table thead th { background: #f0f0f0 !important; font-weight: bold; }
    .badge { border: 1px solid #999 !important; color: black !important; background: #eee !important; padding: 2px 6px !important; border-radius: 4px !important; }
    .text-secondary { color: #555 !important; }
    #print-header { display: block !important; }
    .app-container { display: block !important; }
}
</style>

<?php include '../includes/footer.php'; ?>
