<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
verificarLogin();

$acao = $_GET['acao'] ?? 'listar';
$msg = '';

// Processar exclusão
if ($acao === 'excluir' && isset($_GET['id']) && isAdmin()) {
    $stmt = $pdo->prepare("DELETE FROM garantias WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header('Location: garantias.php?msg=Garantia excluída com sucesso');
    exit;
}

// Processar formulário (Adicionar/Editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome_equipamento = $_POST['nome_equipamento'];
    $numero_serie = $_POST['numero_serie'];
    $data_compra = $_POST['data_compra'];
    $expira_garantia = $_POST['expira_garantia'];
    $fornecedor = $_POST['fornecedor'];
    $responsavel = $_POST['responsavel'];
    $observacoes = $_POST['observacoes'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE garantias SET nome_equipamento=?, numero_serie=?, data_compra=?, expira_garantia=?, fornecedor=?, responsavel=?, observacoes=? WHERE id=?");
        $stmt->execute([$nome_equipamento, $numero_serie, $data_compra, $expira_garantia, $fornecedor, $responsavel, $observacoes, $id]);
        $msg = "Garantia atualizada com sucesso!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO garantias (nome_equipamento, numero_serie, data_compra, expira_garantia, fornecedor, responsavel, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome_equipamento, $numero_serie, $data_compra, $expira_garantia, $fornecedor, $responsavel, $observacoes]);
        $msg = "Garantia cadastrada com sucesso!";
    }
}

$garantias = $pdo->query("SELECT * FROM garantias ORDER BY expira_garantia ASC")->fetchAll();

include '../includes/header.php';
?>

<div class="card bg-navy border-0 rounded-4 shadow-sm mt-4">
    <div class="card-header bg-transparent border-bottom border-secondary d-flex justify-content-between align-items-center p-4">
        <h4 class="m-0 fw-bold">Gestão de Garantias</h4>
        <?php if (isAdmin()): ?>
        <button class="btn btn-info fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalGarantia">
            <i class="fas fa-plus me-2"></i> Nova Garantia
        </button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (isset($_GET['msg']) || $msg): ?>
            <div class="alert alert-success m-3"><?php echo $_GET['msg'] ?? $msg; ?></div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-hover table-dark-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Equipamento</th>
                        <th>Nº de Série</th>
                        <th>Expiração</th>
                        <th>Status</th>
                        <th class="pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($garantias as $g): ?>
                    <tr>
                        <td class="ps-3"><strong><?php echo $g['nome_equipamento']; ?></strong></td>
                        <td><?php echo $g['numero_serie']; ?></td>
                        <td><?php echo formatarData($g['expira_garantia']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $g['status']; ?>">
                                <?php echo getStatusLabel($g['status']); ?>
                            </span>
                        </td>
                        <td class="pe-3">
                            <?php if (isAdmin()): ?>
                            <button class="btn btn-sm btn-outline-info" onclick="editarGarantia(<?php echo htmlspecialchars(json_encode($g)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?acao=excluir&id=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza?')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-light"><i class="fas fa-eye"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGarantia" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Cadastrar Nova Garantia</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row g-4">
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-laptop me-2"></i> Nome do Equipamento</label>
                            <input type="text" name="nome_equipamento" id="edit_nome_equipamento" class="form-control" placeholder="Ex: Notebook Dell XPS" required>
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-barcode me-2"></i> Número de Série</label>
                            <input type="text" name="numero_serie" id="edit_numero_serie" class="form-control" placeholder="Ex: SN12345678">
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-calendar-alt me-2"></i> Data da Compra</label>
                            <input type="date" name="data_compra" id="edit_data_compra" class="form-control">
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-calendar-check me-2"></i> Expiração da Garantia</label>
                            <input type="date" name="expira_garantia" id="edit_expira_garantia" class="form-control" required>
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-industry me-2"></i> Fornecedor</label>
                            <input type="text" name="fornecedor" id="edit_fornecedor" class="form-control" placeholder="Ex: Dell Inc">
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-user-circle me-2"></i> Responsável</label>
                            <input type="text" name="responsavel" id="edit_responsavel" class="form-control" placeholder="Ex: Alice">
                        </div>
                        <div class="col-12 text-start">
                            <label class="form-label"><i class="fas fa-clipboard-list me-2"></i> Observações</label>
                            <textarea name="observacoes" id="edit_observacoes" class="form-control" rows="3" placeholder="Status do item, histórico..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info px-5 rounded-3 fw-bold shadow-sm">Salvar Garantia</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarGarantia(g) {
    document.getElementById('modalTitle').innerText = 'Editar Garantia';
    document.getElementById('edit_id').value = g.id;
    document.getElementById('edit_nome_equipamento').value = g.nome_equipamento;
    document.getElementById('edit_numero_serie').value = g.numero_serie;
    document.getElementById('edit_data_compra').value = g.data_compra;
    document.getElementById('edit_expira_garantia').value = g.expira_garantia;
    document.getElementById('edit_fornecedor').value = g.fornecedor;
    document.getElementById('edit_responsavel').value = g.responsavel;
    document.getElementById('edit_observacoes').value = g.observacoes;
    
    new bootstrap.Modal(document.getElementById('modalGarantia')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
