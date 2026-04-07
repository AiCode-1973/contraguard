<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
verificarLogin();

$acao = $_GET['acao'] ?? 'listar';
$msg = '';

// Processar exclusão
if ($acao === 'excluir' && isset($_GET['id']) && isAdmin()) {
    $stmt = $pdo->prepare("DELETE FROM contratos WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header('Location: contratos.php?msg=Contrato excluído com sucesso');
    exit;
}

// Processar formulário (Adicionar/Editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'];
    $fornecedor = $_POST['fornecedor'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $categoria = $_POST['categoria'];
    $responsavel = $_POST['responsavel'];
    $observacoes = $_POST['observacoes'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE contratos SET nome=?, fornecedor=?, data_inicio=?, data_fim=?, categoria=?, responsavel=?, observacoes=? WHERE id=?");
        $stmt->execute([$nome, $fornecedor, $data_inicio, $data_fim, $categoria, $responsavel, $observacoes, $id]);
        $msg = "Contrato atualizado com sucesso!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO contratos (nome, fornecedor, data_inicio, data_fim, categoria, responsavel, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $fornecedor, $data_inicio, $data_fim, $categoria, $responsavel, $observacoes]);
        $msg = "Contrato cadastrado com sucesso!";
    }
}

$contratos = $pdo->query("SELECT * FROM contratos ORDER BY data_fim ASC")->fetchAll();

include '../includes/header.php';
?>

<div class="card bg-navy border-0 rounded-4 shadow-sm mt-4">
    <div class="card-header bg-transparent border-bottom border-secondary d-flex justify-content-between align-items-center p-4">
        <h4 class="m-0 fw-bold text-white">Gestão de Contratos</h4>
        <?php if (isAdmin()): ?>
        <button class="btn btn-info fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalContrato">
            <i class="fas fa-plus me-2"></i> Novo Contrato
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
                        <th class="ps-3">Nome</th>
                        <th>Fornecedor</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                        <th class="pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($contratos as $c): ?>
                    <tr>
                        <td class="ps-3"><strong><?php echo $c['nome']; ?></strong></td>
                        <td><?php echo $c['fornecedor']; ?></td>
                        <td><?php echo formatarData($c['data_fim']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $c['status']; ?>">
                                <?php echo getStatusLabel($c['status']); ?>
                            </span>
                        </td>
                        <td class="pe-3">
                            <?php if (isAdmin()): ?>
                            <button class="btn btn-sm btn-outline-info" onclick="editarContrato(<?php echo htmlspecialchars(json_encode($c)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?acao=excluir&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza?')">
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

<div class="modal fade" id="modalContrato" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Cadastrar Novo Contrato</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row g-4">
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-file-signature me-2"></i> Nome do Contrato</label>
                            <input type="text" name="nome" id="edit_nome" class="form-control" placeholder="Ex: Manutenção Mensal" required>
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-building me-2"></i> Fornecedor</label>
                            <input type="text" name="fornecedor" id="edit_fornecedor" class="form-control" placeholder="Ex: Dell Technologies">
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-calendar-alt me-2"></i> Data Início</label>
                            <input type="date" name="data_inicio" id="edit_data_inicio" class="form-control">
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-calendar-check me-2"></i> Data Fim (Vencimento)</label>
                            <input type="date" name="data_fim" id="edit_data_fim" class="form-control" required>
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-tags me-2"></i> Categoria</label>
                            <select name="categoria" id="edit_categoria" class="form-select">
                                <option value="Software">Software</option>
                                <option value="Hardware">Hardware</option>
                                <option value="Serviços">Serviços</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-user-tie me-2"></i> Responsável</label>
                            <input type="text" name="responsavel" id="edit_responsavel" class="form-control" placeholder="Ex: Demetrius">
                        </div>
                        <div class="col-12 text-start">
                            <label class="form-label"><i class="fas fa-comment-dots me-2"></i> Observações</label>
                            <textarea name="observacoes" id="edit_observacoes" class="form-control" rows="3" placeholder="Detalhes adicionais..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info px-5 rounded-3 fw-bold shadow-sm">Salvar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarContrato(c) {
    document.getElementById('modalTitle').innerText = 'Editar Contrato';
    document.getElementById('edit_id').value = c.id;
    document.getElementById('edit_nome').value = c.nome;
    document.getElementById('edit_fornecedor').value = c.fornecedor;
    document.getElementById('edit_data_inicio').value = c.data_inicio;
    document.getElementById('edit_data_fim').value = c.data_fim;
    document.getElementById('edit_categoria').value = c.categoria;
    document.getElementById('edit_responsavel').value = c.responsavel;
    document.getElementById('edit_observacoes').value = c.observacoes;
    
    new bootstrap.Modal(document.getElementById('modalContrato')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
