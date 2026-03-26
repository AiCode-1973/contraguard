<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
verificarLogin();

if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$status = $_GET['status'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$periodo = $_GET['periodo'] ?? '';

$query_contratos = "SELECT 'Contrato' as tipo, nome, fornecedor, data_fim as vencimento, status FROM contratos WHERE 1=1";
$params_contratos = [];

if ($status) {
    $query_contratos .= " AND status = ?";
    $params_contratos[] = $status;
}

if ($categoria) {
    $query_contratos .= " AND categoria = ?";
    $params_contratos[] = $categoria;
}

$query_garantias = "SELECT 'Garantia' as tipo, nome_equipamento as nome, fornecedor, expira_garantia as vencimento, status FROM garantias WHERE 1=1";
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

<div class="card p-3 mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label text-secondary">Status</label>
            <select name="status" class="form-select bg-navy text-white border-secondary">
                <option value="">Todos</option>
                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Ativos</option>
                <option value="expiring" <?php echo $status == 'expiring' ? 'selected' : ''; ?>>Vencendo</option>
                <option value="expired" <?php echo $status == 'expired' ? 'selected' : ''; ?>>Vencidos</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label text-secondary">Categoria</label>
            <input type="text" name="categoria" class="form-control bg-navy text-white border-secondary" placeholder="Ex: Software" value="<?php echo $categoria; ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-info w-100">Filtrar</button>
        </div>
    </form>
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
                        <th>Vencimento</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($dados as $d): ?>
                    <tr>
                        <td class="ps-3"><span class="badge bg-secondary"><?php echo $d['tipo']; ?></span></td>
                        <td><strong><?php echo $d['nome']; ?></strong></td>
                        <td><?php echo $d['fornecedor']; ?></td>
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
    .sidebar, .btn, form, .badge-info { display: none !important; }
    .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
    .card { border: none !important; }
    body { background: white !important; color: black !important; }
    .text-white { color: black !important; }
    .bg-navy, .bg-dark { background: white !important; }
    .table { color: black !important; }
}
</style>

<?php include '../includes/footer.php'; ?>
