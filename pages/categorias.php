<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
verificarLogin();
if (!isAdmin()) { header('Location: ' . APP_URL . '/index.php'); exit; }

// ── Migração: cria tabela categorias ─────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS categorias (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(100) NOT NULL UNIQUE,
    descricao  VARCHAR(255) DEFAULT NULL,
    criado_em  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Seed: garante que as categorias padrão existam
$seed = ['Hardware', 'Outros', 'Serviços', 'Software'];
$ins_seed = $pdo->prepare("INSERT IGNORE INTO categorias (nome) VALUES (?)");
foreach ($seed as $s) { $ins_seed->execute([$s]); }
$msg  = '';
$tipo = '';
$acao = $_POST['acao'] ?? '';

// ── POST: salvar ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $id       = (int)($_POST['id'] ?? 0);

    if ($nome === '') {
        $msg = 'O nome da categoria é obrigatório.';
        $tipo = 'danger';
    } else {
        if ($acao === 'excluir' && $id > 0) {
            $pdo->prepare("DELETE FROM categorias WHERE id = ?")->execute([$id]);
            $msg = 'Categoria excluída com sucesso.';
            $tipo = 'success';
        } elseif ($acao === 'editar' && $id > 0) {
            $stmt = $pdo->prepare("UPDATE categorias SET nome = ?, descricao = ? WHERE id = ?");
            $stmt->execute([$nome, $descricao ?: null, $id]);
            $msg = 'Categoria atualizada com sucesso.';
            $tipo = 'success';
        } else {
            // Verificar duplicata
            $dup = $pdo->prepare("SELECT id FROM categorias WHERE nome = ?");
            $dup->execute([$nome]);
            if ($dup->fetch()) {
                $msg = 'Já existe uma categoria com esse nome.';
                $tipo = 'danger';
            } else {
                $pdo->prepare("INSERT INTO categorias (nome, descricao) VALUES (?, ?)")
                    ->execute([$nome, $descricao ?: null]);
                $msg = 'Categoria cadastrada com sucesso.';
                $tipo = 'success';
            }
        }
    }
}

// ── Listar ────────────────────────────────────────────────────────────────
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();

// Contar uso em contratos
$uso_contratos = [];
$rows_c = $pdo->query("SELECT categoria, COUNT(*) as qtd FROM contratos GROUP BY categoria")->fetchAll();
foreach ($rows_c as $r) $uso_contratos[$r['categoria']] = $r['qtd'];

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-tags me-2 text-info"></i>Categorias</h4>
        <p class="text-secondary small mb-0">Gerencie as categorias utilizadas nos contratos</p>
    </div>
    <button class="btn btn-info fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalCategoria" id="btnNovaCategoria">
        <i class="fas fa-plus me-2"></i>Nova Categoria
    </button>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $tipo; ?> alert-dismissible fade show" role="alert">
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
                    <th class="text-center" style="width:110px">Contratos</th>
                    <th class="text-center" style="width:120px">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categorias)): ?>
                <tr>
                    <td colspan="4" class="text-center text-secondary p-5">
                        <i class="fas fa-tags fa-2x mb-2 d-block opacity-25"></i>
                        Nenhuma categoria cadastrada.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($categorias as $cat): ?>
                <tr>
                    <td class="ps-4 fw-bold"><?php echo htmlspecialchars($cat['nome']); ?></td>
                    <td class="text-secondary"><?php echo htmlspecialchars($cat['descricao'] ?? '—'); ?></td>
                    <td class="text-center">
                        <span class="badge bg-secondary"><?php echo $uso_contratos[$cat['nome']] ?? 0; ?></span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-info me-1"
                                onclick="editarCategoria(<?php echo $cat['id']; ?>, '<?php echo addslashes(htmlspecialchars($cat['nome'])); ?>', '<?php echo addslashes(htmlspecialchars($cat['descricao'] ?? '')); ?>')"
                                title="Editar">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="confirmarExclusao(<?php echo $cat['id']; ?>, '<?php echo addslashes(htmlspecialchars($cat['nome'])); ?>', <?php echo $uso_contratos[$cat['nome']] ?? 0; ?>)"
                                title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Criar/Editar -->
<div class="modal fade" id="modalCategoria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST">
                <input type="hidden" name="id" id="modal_id" value="0">
                <input type="hidden" name="acao" id="modal_acao" value="">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title fw-bold" id="modalTitle">
                        <i class="fas fa-tags me-2 text-info"></i>Nova Categoria
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="nome" id="modal_nome" class="form-control" placeholder="Ex: Serviços de TI" required maxlength="100">
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

<!-- Modal Confirmar Exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="id" id="excluir_id">
                <input type="hidden" name="nome" id="excluir_nome_hidden">
                <div class="modal-header border-bottom border-secondary border-danger">
                    <h5 class="modal-title fw-bold text-danger"><i class="fas fa-triangle-exclamation me-2"></i>Excluir Categoria</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p>Tem certeza que deseja excluir a categoria <strong id="excluir_nome_label"></strong>?</p>
                    <div id="aviso_uso" class="alert alert-warning d-none">
                        <i class="fas fa-triangle-exclamation me-2"></i>
                        Esta categoria está sendo usada em <strong id="aviso_uso_qtd"></strong> contrato(s). A exclusão não remove os contratos, mas o campo categoria ficará sem vínculo.
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4">
                        <i class="fas fa-trash me-2"></i>Excluir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('btnNovaCategoria').addEventListener('click', function() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-tags me-2 text-info"></i>Nova Categoria';
    document.getElementById('modal_id').value = '0';
    document.getElementById('modal_acao').value = '';
    document.getElementById('modal_nome').value = '';
    document.getElementById('modal_descricao').value = '';
    document.getElementById('modal_btn_salvar').innerHTML = '<i class="fas fa-save me-2"></i>Salvar';
});

function editarCategoria(id, nome, descricao) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-pen me-2 text-info"></i>Editar Categoria';
    document.getElementById('modal_id').value = id;
    document.getElementById('modal_acao').value = 'editar';
    document.getElementById('modal_nome').value = nome;
    document.getElementById('modal_descricao').value = descricao;
    document.getElementById('modal_btn_salvar').innerHTML = '<i class="fas fa-save me-2"></i>Atualizar';
    new bootstrap.Modal(document.getElementById('modalCategoria')).show();
}

function confirmarExclusao(id, nome, qtdUso) {
    document.getElementById('excluir_id').value = id;
    document.getElementById('excluir_nome_hidden').value = nome;
    document.getElementById('excluir_nome_label').textContent = nome;
    var aviso = document.getElementById('aviso_uso');
    if (qtdUso > 0) {
        aviso.classList.remove('d-none');
        document.getElementById('aviso_uso_qtd').textContent = qtdUso;
    } else {
        aviso.classList.add('d-none');
    }
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
