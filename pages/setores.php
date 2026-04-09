<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
verificarLogin();
if (!isAdmin()) { header('Location: ' . APP_URL . '/index.php'); exit; }

// ── Migração ──────────────────────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS setores (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(100) NOT NULL UNIQUE,
    descricao  VARCHAR(255) DEFAULT NULL,
    criado_em  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg  = '';
$tipo = '';
$acao = $_POST['acao'] ?? '';

// ── POST ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $id        = (int)($_POST['id'] ?? 0);

    if ($nome === '') {
        $msg  = 'O nome do setor é obrigatório.';
        $tipo = 'danger';
    } elseif ($acao === 'excluir' && $id > 0) {
        // Verificar se há usuários no setor
        $em_uso = (int)$pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE setor_id = ?")->execute([$id]) ?
                  $pdo->query("SELECT COUNT(*) FROM usuarios WHERE setor_id = $id")->fetchColumn() : 0;
        if ($em_uso > 0) {
            $msg  = "Este setor possui $em_uso usuário(s) vinculado(s). Remova o vínculo antes de excluir.";
            $tipo = 'danger';
        } else {
            $pdo->prepare("DELETE FROM setores WHERE id = ?")->execute([$id]);
            $msg  = 'Setor excluído com sucesso.';
            $tipo = 'success';
        }
    } elseif ($acao === 'editar' && $id > 0) {
        $dup = $pdo->prepare("SELECT id FROM setores WHERE nome = ? AND id != ?");
        $dup->execute([$nome, $id]);
        if ($dup->fetch()) {
            $msg = 'Já existe um setor com esse nome.'; $tipo = 'danger';
        } else {
            $pdo->prepare("UPDATE setores SET nome = ?, descricao = ? WHERE id = ?")
                ->execute([$nome, $descricao ?: null, $id]);
            $msg = 'Setor atualizado com sucesso.'; $tipo = 'success';
        }
    } else {
        $dup = $pdo->prepare("SELECT id FROM setores WHERE nome = ?");
        $dup->execute([$nome]);
        if ($dup->fetch()) {
            $msg = 'Já existe um setor com esse nome.'; $tipo = 'danger';
        } else {
            $pdo->prepare("INSERT INTO setores (nome, descricao) VALUES (?, ?)")
                ->execute([$nome, $descricao ?: null]);
            $msg = 'Setor cadastrado com sucesso.'; $tipo = 'success';
        }
    }
}

// ── Listar ────────────────────────────────────────────────────────────────
$setores = $pdo->query("SELECT s.*, COUNT(u.id) as total_usuarios
    FROM setores s
    LEFT JOIN usuarios u ON u.setor_id = s.id
    GROUP BY s.id ORDER BY s.nome ASC")->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-building me-2 text-info"></i>Setores</h4>
        <p class="text-secondary small mb-0">Gerencie os setores da organização</p>
    </div>
    <button class="btn btn-info fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalSetor" id="btnNovoSetor">
        <i class="fas fa-plus me-2"></i>Novo Setor
    </button>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $tipo; ?> alert-dismissible fade show">
    <?php echo htmlspecialchars($msg); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card border-0 rounded-4 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4" style="width:40%">Nome</th>
                    <th>Descrição</th>
                    <th class="text-center" style="width:110px">Usuários</th>
                    <th class="text-center" style="width:120px">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($setores)): ?>
                <tr><td colspan="4" class="text-center text-secondary p-5">
                    <i class="fas fa-building fa-2x mb-2 d-block opacity-25"></i>Nenhum setor cadastrado.
                </td></tr>
                <?php else: ?>
                <?php foreach ($setores as $s): ?>
                <tr>
                    <td class="ps-4 fw-bold"><?php echo htmlspecialchars($s['nome']); ?></td>
                    <td class="text-secondary"><?php echo htmlspecialchars($s['descricao'] ?? '—'); ?></td>
                    <td class="text-center"><span class="badge bg-secondary"><?php echo $s['total_usuarios']; ?></span></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-info me-1"
                            onclick="editarSetor(<?php echo $s['id']; ?>, '<?php echo addslashes(htmlspecialchars($s['nome'])); ?>', '<?php echo addslashes(htmlspecialchars($s['descricao'] ?? '')); ?>')"
                            title="Editar"><i class="fas fa-pen"></i></button>
                        <button class="btn btn-sm btn-outline-danger"
                            onclick="confirmarExclusao(<?php echo $s['id']; ?>, '<?php echo addslashes(htmlspecialchars($s['nome'])); ?>', <?php echo $s['total_usuarios']; ?>)"
                            title="Excluir"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Criar/Editar -->
<div class="modal fade" id="modalSetor" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST">
                <input type="hidden" name="id" id="modal_id" value="0">
                <input type="hidden" name="acao" id="modal_acao" value="">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title fw-bold" id="modalTitle">
                        <i class="fas fa-building me-2 text-info"></i>Novo Setor
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="nome" id="modal_nome" class="form-control" placeholder="Ex: Tecnologia da Informação" required maxlength="100">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Descrição</label>
                        <input type="text" name="descricao" id="modal_descricao" class="form-control" placeholder="Opcional" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info fw-bold px-4" id="modal_btn_salvar">
                        <i class="fas fa-save me-2"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Excluir -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="id" id="excluir_id">
                <input type="hidden" name="nome" id="excluir_nome_hidden">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title fw-bold text-danger"><i class="fas fa-triangle-exclamation me-2"></i>Excluir Setor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p>Tem certeza que deseja excluir o setor <strong id="excluir_nome_label"></strong>?</p>
                    <div id="aviso_uso" class="alert alert-warning d-none">
                        <i class="fas fa-triangle-exclamation me-2"></i>
                        Este setor possui <strong id="aviso_qtd"></strong> usuário(s) vinculado(s). Remova os vínculos antes de excluir.
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4"><i class="fas fa-trash me-2"></i>Excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('btnNovoSetor').addEventListener('click', function () {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-building me-2 text-info"></i>Novo Setor';
    document.getElementById('modal_id').value = '0';
    document.getElementById('modal_acao').value = '';
    document.getElementById('modal_nome').value = '';
    document.getElementById('modal_descricao').value = '';
    document.getElementById('modal_btn_salvar').innerHTML = '<i class="fas fa-save me-2"></i>Salvar';
});

function editarSetor(id, nome, descricao) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-pen me-2 text-info"></i>Editar Setor';
    document.getElementById('modal_id').value = id;
    document.getElementById('modal_acao').value = 'editar';
    document.getElementById('modal_nome').value = nome;
    document.getElementById('modal_descricao').value = descricao;
    document.getElementById('modal_btn_salvar').innerHTML = '<i class="fas fa-save me-2"></i>Atualizar';
    new bootstrap.Modal(document.getElementById('modalSetor')).show();
}

function confirmarExclusao(id, nome, qtd) {
    document.getElementById('excluir_id').value = id;
    document.getElementById('excluir_nome_hidden').value = nome;
    document.getElementById('excluir_nome_label').textContent = nome;
    var aviso = document.getElementById('aviso_uso');
    if (qtd > 0) {
        aviso.classList.remove('d-none');
        document.getElementById('aviso_qtd').textContent = qtd;
    } else {
        aviso.classList.add('d-none');
    }
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
