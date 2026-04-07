<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
verificarLogin();

// ── Migrações automáticas ──────────────────────────────────────────────────
$colunas_c = $pdo->query("SHOW COLUMNS FROM contratos")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('valor', $colunas_c)) {
    $pdo->exec("ALTER TABLE contratos ADD COLUMN valor DECIMAL(15,2) DEFAULT NULL");
}

$pdo->exec("CREATE TABLE IF NOT EXISTS contrato_anexos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    contrato_id INT NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    nome_arquivo  VARCHAR(255) NOT NULL,
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$upload_dir = __DIR__ . '/../uploads/contratos/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// ── Helpers ───────────────────────────────────────────────────────────────
function formatarValor($v) {
    return 'R$ ' . number_format((float)$v, 2, ',', '.');
}

$extensoes_permitidas = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
$tamanho_maximo = 10 * 1024 * 1024; // 10 MB

$acao = $_GET['acao'] ?? 'listar';
$msg  = '';

// ── Excluir contrato ──────────────────────────────────────────────────────
if ($acao === 'excluir' && isset($_GET['id']) && isAdmin()) {
    $id_del = (int)$_GET['id'];
    // Apaga arquivos físicos do contrato
    $anexos_del = $pdo->prepare("SELECT nome_arquivo FROM contrato_anexos WHERE contrato_id = ?");
    $anexos_del->execute([$id_del]);
    foreach ($anexos_del->fetchAll() as $a) {
        $caminho = $upload_dir . $a['nome_arquivo'];
        if (file_exists($caminho)) unlink($caminho);
    }
    $stmt = $pdo->prepare("DELETE FROM contratos WHERE id = ?");
    $stmt->execute([$id_del]);
    header('Location: contratos.php?msg=Contrato excluído com sucesso');
    exit;
}

// ── Excluir anexo individual ──────────────────────────────────────────────
if ($acao === 'excluir_anexo' && isset($_GET['id']) && isAdmin()) {
    $id_anexo = (int)$_GET['id'];
    $row = $pdo->prepare("SELECT nome_arquivo, contrato_id FROM contrato_anexos WHERE id = ?");
    $row->execute([$id_anexo]);
    $anexo = $row->fetch();
    if ($anexo) {
        $caminho = $upload_dir . $anexo['nome_arquivo'];
        if (file_exists($caminho)) unlink($caminho);
        $pdo->prepare("DELETE FROM contrato_anexos WHERE id = ?")->execute([$id_anexo]);
        header('Location: contratos.php?msg=Anexo removido com sucesso');
    }
    exit;
}

// ── Processar formulário (Adicionar/Editar) ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = $_POST['id'] ?? null;
    $nome        = $_POST['nome'];
    $fornecedor  = $_POST['fornecedor'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim    = $_POST['data_fim'];
    $categoria   = $_POST['categoria'];
    $responsavel = $_POST['responsavel'];
    $observacoes = $_POST['observacoes'];
    $valor       = !empty($_POST['valor']) ? preg_replace('/[^\d,]/', '', $_POST['valor']) : null;
    if ($valor !== null) {
        $valor = str_replace(',', '.', str_replace('.', '', $valor));
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE contratos SET nome=?, fornecedor=?, data_inicio=?, data_fim=?, categoria=?, responsavel=?, observacoes=?, valor=? WHERE id=?");
        $stmt->execute([$nome, $fornecedor, $data_inicio, $data_fim, $categoria, $responsavel, $observacoes, $valor, $id]);
        $msg = "Contrato atualizado com sucesso!";
        $contrato_id = (int)$id;
    } else {
        $stmt = $pdo->prepare("INSERT INTO contratos (nome, fornecedor, data_inicio, data_fim, categoria, responsavel, observacoes, valor) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $fornecedor, $data_inicio, $data_fim, $categoria, $responsavel, $observacoes, $valor]);
        $contrato_id = (int)$pdo->lastInsertId();
        $msg = "Contrato cadastrado com sucesso!";
    }

    // Upload de anexos
    if (!empty($_FILES['anexos']['name'][0])) {
        $erros_upload = [];
        foreach ($_FILES['anexos']['tmp_name'] as $i => $tmp) {
            if ($_FILES['anexos']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $nome_original = $_FILES['anexos']['name'][$i];
            $ext = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
            if (!in_array($ext, $extensoes_permitidas)) {
                $erros_upload[] = "\"$nome_original\" não permitido (use PDF, DOC, DOCX, PNG, JPG).";
                continue;
            }
            if ($_FILES['anexos']['size'][$i] > $tamanho_maximo) {
                $erros_upload[] = "\"$nome_original\" excede o limite de 10 MB.";
                continue;
            }
            $nome_arquivo = uniqid('contrato_' . $contrato_id . '_') . '.' . $ext;
            move_uploaded_file($tmp, $upload_dir . $nome_arquivo);
            $pdo->prepare("INSERT INTO contrato_anexos (contrato_id, nome_original, nome_arquivo) VALUES (?, ?, ?)")
                ->execute([$contrato_id, $nome_original, $nome_arquivo]);
        }
        if ($erros_upload) {
            $msg .= ' Atenção: ' . implode(' ', $erros_upload);
        }
    }
}

// ── Buscar contratos com contagem de anexos ───────────────────────────────
$contratos_raw = $pdo->query(
    "SELECT c.*, COUNT(a.id) as total_anexos
     FROM contratos c
     LEFT JOIN contrato_anexos a ON a.contrato_id = c.id
     GROUP BY c.id
     ORDER BY c.data_fim ASC"
)->fetchAll();

// Annexar lista de arquivos para o modal de edição
$contratos = [];
foreach ($contratos_raw as $c) {
    $stmt_a = $pdo->prepare("SELECT * FROM contrato_anexos WHERE contrato_id = ?");
    $stmt_a->execute([$c['id']]);
    $c['_anexos'] = $stmt_a->fetchAll();
    $contratos[] = $c;
}

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
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Anexos</th>
                        <th>Status</th>
                        <th class="pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($contratos as $c): ?>
                    <tr>
                        <td class="ps-3"><strong><?php echo htmlspecialchars($c['nome']); ?></strong></td>
                        <td><?php echo htmlspecialchars($c['fornecedor']); ?></td>
                        <td><?php echo $c['valor'] ? formatarValor($c['valor']) : '<span class="text-secondary">—</span>'; ?></td>
                        <td><?php echo formatarData($c['data_fim']); ?></td>
                        <td>
                            <?php if ($c['total_anexos'] > 0): ?>
                                <button class="btn btn-sm btn-outline-secondary" title="Ver anexos"
                                    onclick="verAnexos(<?php echo htmlspecialchars(json_encode($c), ENT_QUOTES); ?>)">
                                    <i class="fas fa-paperclip me-1"></i><?php echo $c['total_anexos']; ?>
                                </button>
                            <?php else: ?>
                                <span class="text-secondary">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $c['status']; ?>">
                                <?php echo getStatusLabel($c['status']); ?>
                            </span>
                        </td>
                        <td class="pe-3">
                            <?php if (isAdmin()): ?>
                            <button class="btn btn-sm btn-outline-info" onclick="editarContrato(<?php echo htmlspecialchars(json_encode($c), ENT_QUOTES); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?acao=excluir&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir este contrato e todos os seus anexos?')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Visualizar Anexos -->
<div class="modal fade" id="modalVisualizarAnexos" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAnexosTitle"><i class="fas fa-paperclip me-2"></i>Anexos do Contrato</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalAnexosBody">
                <p class="text-secondary">Nenhum anexo.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary px-4 rounded-3" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalContrato" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
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
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-dollar-sign me-2"></i> Valor do Contrato</label>
                            <input type="text" name="valor" id="edit_valor" class="form-control" placeholder="Ex: 1.500,00" inputmode="decimal">
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-paperclip me-2"></i> Anexar Arquivos</label>
                            <input type="file" name="anexos[]" id="edit_anexos" class="form-control" multiple
                                accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                            <div class="form-text text-secondary">PDF, DOC, DOCX, PNG, JPG — máx. 10 MB cada</div>
                        </div>
                        <div class="col-12 text-start" id="lista_anexos_container" style="display:none;">
                            <label class="form-label"><i class="fas fa-folder-open me-2"></i> Anexos Existentes</label>
                            <ul class="list-group list-group-flush" id="lista_anexos"></ul>
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

    // Valor: formatar como BRL para exibição no input
    if (c.valor) {
        var v = parseFloat(c.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('edit_valor').value = v;
    } else {
        document.getElementById('edit_valor').value = '';
    }

    // Anexos existentes
    var container = document.getElementById('lista_anexos_container');
    var lista = document.getElementById('lista_anexos');
    lista.innerHTML = '';
    if (c._anexos && c._anexos.length > 0) {
        c._anexos.forEach(function(a) {
            var li = document.createElement('li');
            li.className = 'list-group-item bg-transparent text-white border-secondary d-flex justify-content-between align-items-center py-1';
            li.innerHTML = '<span><i class="fas fa-file me-2 text-info"></i>' + escHtml(a.nome_original) + '</span>'
                + '<a href="?acao=excluir_anexo&id=' + a.id + '" class="btn btn-sm btn-outline-danger py-0 px-2" '
                + 'onclick="return confirm(\'Remover este anexo?\')"><i class="fas fa-times"></i></a>';
            lista.appendChild(li);
        });
        container.style.display = '';
    } else {
        container.style.display = 'none';
    }

    new bootstrap.Modal(document.getElementById('modalContrato')).show();
}

var APP_URL = '<?php echo APP_URL; ?>';

function iconeAnexo(ext) {
    if (['png','jpg','jpeg'].includes(ext)) return 'fa-file-image text-warning';
    if (ext === 'pdf') return 'fa-file-pdf text-danger';
    if (['doc','docx'].includes(ext)) return 'fa-file-word text-primary';
    return 'fa-file text-info';
}

function verAnexos(c) {
    document.getElementById('modalAnexosTitle').innerHTML = '<i class="fas fa-paperclip me-2"></i>Anexos — ' + escHtml(c.nome);
    var body = document.getElementById('modalAnexosBody');
    if (!c._anexos || c._anexos.length === 0) {
        body.innerHTML = '<p class="text-secondary">Nenhum anexo cadastrado.</p>';
    } else {
        var html = '<ul class="list-group list-group-flush">';
        c._anexos.forEach(function(a) {
            var ext = a.nome_arquivo.split('.').pop().toLowerCase();
            html += '<li class="list-group-item bg-transparent text-white border-secondary d-flex justify-content-between align-items-center py-2">'
                + '<span><i class="fas ' + iconeAnexo(ext) + ' me-2"></i>' + escHtml(a.nome_original) + '</span>'
                + '<div class="d-flex gap-2">'
                + '<a href="' + APP_URL + '/pages/download_anexo.php?id=' + a.id + '" target="_blank" class="btn btn-sm btn-outline-info"><i class="fas fa-eye me-1"></i>Visualizar</a>'
                + '<a href="' + APP_URL + '/pages/download_anexo.php?id=' + a.id + '&download=1" class="btn btn-sm btn-outline-success"><i class="fas fa-download me-1"></i>Baixar</a>'
                + '</div>'
                + '</li>';
        });
        html += '</ul>';
        body.innerHTML = html;
    }
    new bootstrap.Modal(document.getElementById('modalVisualizarAnexos')).show();
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Resetar modal ao abrir via botão "Novo Contrato"
document.querySelector('[data-bs-target="#modalContrato"]').addEventListener('click', function() {
    document.getElementById('modalTitle').innerText = 'Cadastrar Novo Contrato';
    document.getElementById('edit_id').value = '';
    document.getElementById('edit_nome').value = '';
    document.getElementById('edit_fornecedor').value = '';
    document.getElementById('edit_data_inicio').value = '';
    document.getElementById('edit_data_fim').value = '';
    document.getElementById('edit_categoria').value = 'Software';
    document.getElementById('edit_responsavel').value = '';
    document.getElementById('edit_observacoes').value = '';
    document.getElementById('edit_valor').value = '';
    document.getElementById('edit_anexos').value = '';
    document.getElementById('lista_anexos_container').style.display = 'none';
    document.getElementById('lista_anexos').innerHTML = '';
});
</script>

<?php include '../includes/footer.php'; ?>
