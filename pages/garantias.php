<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
verificarLogin();

// ── Migrações automáticas ──────────────────────────────────────────────────
$colunas_g = $pdo->query("SHOW COLUMNS FROM garantias")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('tipo_garantia', $colunas_g)) {
    $pdo->exec("ALTER TABLE garantias ADD COLUMN tipo_garantia VARCHAR(20) DEFAULT NULL");
}
if (!in_array('qtd_anos', $colunas_g)) {
    $pdo->exec("ALTER TABLE garantias ADD COLUMN qtd_anos INT DEFAULT NULL");
}
if (!in_array('usuario_id', $colunas_g)) {
    $pdo->exec("ALTER TABLE garantias ADD COLUMN usuario_id INT DEFAULT NULL");
}

$pdo->exec("CREATE TABLE IF NOT EXISTS garantia_anexos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    garantia_id  INT NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    nome_arquivo  VARCHAR(255) NOT NULL,
    criado_em    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (garantia_id) REFERENCES garantias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$upload_dir = __DIR__ . '/../uploads/garantias/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$extensoes_permitidas = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
$tamanho_maximo = 10 * 1024 * 1024; // 10 MB

$acao = $_GET['acao'] ?? 'listar';
$msg  = '';

// ── Excluir garantia ──────────────────────────────────────────────────────
if ($acao === 'excluir' && isset($_GET['id']) && canEdit()) {
    $id_del = (int)$_GET['id'];
    if (isGestor()) {
        $chk = $pdo->prepare("SELECT id FROM garantias WHERE id = ? AND usuario_id = ?");
        $chk->execute([$id_del, $_SESSION['usuario_id']]);
        if (!$chk->fetch()) { header('Location: garantias.php'); exit; }
    }
    $anexos_del = $pdo->prepare("SELECT nome_arquivo FROM garantia_anexos WHERE garantia_id = ?");
    $anexos_del->execute([$id_del]);
    foreach ($anexos_del->fetchAll() as $a) {
        $caminho = $upload_dir . $a['nome_arquivo'];
        if (file_exists($caminho)) unlink($caminho);
    }
    $pdo->prepare("DELETE FROM garantias WHERE id = ?")->execute([$id_del]);
    header('Location: garantias.php?msg=Garantia excluída com sucesso');
    exit;
}

// ── Excluir anexo individual ──────────────────────────────────────────────
if ($acao === 'excluir_anexo' && isset($_GET['id']) && canEdit()) {
    $id_anexo = (int)$_GET['id'];
    $row = $pdo->prepare("SELECT nome_arquivo FROM garantia_anexos WHERE id = ?");
    $row->execute([$id_anexo]);
    $anexo = $row->fetch();
    if ($anexo) {
        $caminho = $upload_dir . $anexo['nome_arquivo'];
        if (file_exists($caminho)) unlink($caminho);
        $pdo->prepare("DELETE FROM garantia_anexos WHERE id = ?")->execute([$id_anexo]);
        header('Location: garantias.php?msg=Anexo removido com sucesso');
    }
    exit;
}

// ── Processar formulário (Adicionar/Editar) ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && canEdit()) {
    $id               = $_POST['id'] ?? null;
    $nome_equipamento = $_POST['nome_equipamento'];
    $numero_serie     = $_POST['numero_serie'];
    $data_compra      = $_POST['data_compra'];
    $expira_garantia  = $_POST['expira_garantia'];
    $fornecedor       = $_POST['fornecedor'];
    $responsavel      = $_POST['responsavel'];
    $observacoes      = $_POST['observacoes'];
    $tipo_garantia    = !empty($_POST['tipo_garantia']) ? $_POST['tipo_garantia'] : null;
    $qtd_anos         = ($tipo_garantia === 'Personalizado' && !empty($_POST['qtd_anos'])) ? (int)$_POST['qtd_anos'] : null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE garantias SET nome_equipamento=?, numero_serie=?, data_compra=?, expira_garantia=?, fornecedor=?, responsavel=?, observacoes=?, tipo_garantia=?, qtd_anos=? WHERE id=?");
        $stmt->execute([$nome_equipamento, $numero_serie, $data_compra, $expira_garantia, $fornecedor, $responsavel, $observacoes, $tipo_garantia, $qtd_anos, $id]);
        $msg = "Garantia atualizada com sucesso!";
        $garantia_id = (int)$id;
    } else {
        $stmt = $pdo->prepare("INSERT INTO garantias (nome_equipamento, numero_serie, data_compra, expira_garantia, fornecedor, responsavel, observacoes, tipo_garantia, qtd_anos, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome_equipamento, $numero_serie, $data_compra, $expira_garantia, $fornecedor, $responsavel, $observacoes, $tipo_garantia, $qtd_anos, $_SESSION['usuario_id']]);
        $garantia_id = (int)$pdo->lastInsertId();
        $msg = "Garantia cadastrada com sucesso!";
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
            $nome_arquivo = uniqid('garantia_' . $garantia_id . '_') . '.' . $ext;
            move_uploaded_file($tmp, $upload_dir . $nome_arquivo);
            $pdo->prepare("INSERT INTO garantia_anexos (garantia_id, nome_original, nome_arquivo) VALUES (?, ?, ?)")
                ->execute([$garantia_id, $nome_original, $nome_arquivo]);
        }
        if ($erros_upload) {
            $msg .= ' Atenção: ' . implode(' ', $erros_upload);
        }
    }
}

// Mensagens via GET
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}

// ── Buscar garantias com contagem de anexos ───────────────────────────────
$where_g = '';
$params_g = [];
if (!isAdmin()) {
    $where_g = 'WHERE g.usuario_id = ?';
    $params_g = [(int)$_SESSION['usuario_id']];
}
$stmt_g = $pdo->prepare(
    "SELECT g.*, COUNT(a.id) as total_anexos
     FROM garantias g
     LEFT JOIN garantia_anexos a ON a.garantia_id = g.id
     $where_g
     GROUP BY g.id
     ORDER BY g.expira_garantia ASC"
);
$stmt_g->execute($params_g);
$garantias_raw = $stmt_g->fetchAll();

$garantias = [];
foreach ($garantias_raw as $g) {
    $stmt_a = $pdo->prepare("SELECT * FROM garantia_anexos WHERE garantia_id = ?");
    $stmt_a->execute([$g['id']]);
    $g['_anexos'] = $stmt_a->fetchAll();
    $garantias[] = $g;
}

include '../includes/header.php';
?>

<div class="card bg-navy border-0 rounded-4 shadow-sm mt-4">
    <div class="card-header bg-transparent border-bottom border-secondary d-flex justify-content-between align-items-center p-4">
        <h4 class="m-0 fw-bold text-white">Gestão de Garantias</h4>
        <?php if (canEdit()): ?>
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
                        <th>Tipo</th>
                        <th>Anexos</th>
                        <th>Expiração</th>
                        <th>Status</th>
                        <th class="pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($garantias as $g): ?>
                    <tr>
                        <td class="ps-3"><strong><?php echo htmlspecialchars($g['nome_equipamento']); ?></strong></td>
                        <td><?php echo htmlspecialchars($g['numero_serie']); ?></td>
                        <td><?php echo !empty($g['tipo_garantia']) ? formatarTipoContrato($g['tipo_garantia'], $g['qtd_anos']) : '<span class="text-secondary">—</span>'; ?></td>
                        <td>
                            <?php if ($g['total_anexos'] > 0): ?>
                                <button class="btn btn-sm btn-outline-secondary" title="Ver anexos"
                                    onclick="verAnexos(<?php echo htmlspecialchars(json_encode($g), ENT_QUOTES); ?>)">
                                    <i class="fas fa-paperclip me-1"></i><?php echo $g['total_anexos']; ?>
                                </button>
                            <?php else: ?>
                                <span class="text-secondary">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatarData($g['expira_garantia']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $g['status']; ?>">
                                <?php echo getStatusLabel($g['status']); ?>
                            </span>
                        </td>
                        <td class="pe-3">
                            <?php
                            $pode_editar_g = isAdmin() || (isGestor() && (int)($g['usuario_id'] ?? 0) === (int)$_SESSION['usuario_id']);
                            ?>
                            <?php if ($pode_editar_g): ?>
                            <button class="btn btn-sm btn-outline-info" onclick="editarGarantia(<?php echo htmlspecialchars(json_encode($g), ENT_QUOTES); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?acao=excluir&id=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir esta garantia e todos os seus anexos?')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" onclick="editarGarantia(<?php echo htmlspecialchars(json_encode($g), ENT_QUOTES); ?>)" title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalVisualizarAnexos" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAnexosTitle"><i class="fas fa-paperclip me-2"></i>Anexos da Garantia</h5>
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

<div class="modal fade" id="modalGarantia" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
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
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-rotate me-2"></i> Tipo de Garantia</label>
                            <select name="tipo_garantia" id="edit_tipo_garantia" class="form-select" onchange="toggleQtdAnos(this.value)">
                                <option value="">Não especificado</option>
                                <option value="Mensal">Mensal</option>
                                <option value="Anual">Anual</option>
                                <option value="Personalizado">Personalizado (anos)</option>
                            </select>
                        </div>
                        <div class="col-md-6 text-start" id="campo_qtd_anos" style="display:none;">
                            <label class="form-label"><i class="fas fa-hashtag me-2"></i> Quantidade de Anos</label>
                            <input type="number" name="qtd_anos" id="edit_qtd_anos" class="form-control" min="1" max="99" placeholder="Ex: 3">
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
var APP_URL = '<?php echo APP_URL; ?>';

function toggleQtdAnos(val) {
    document.getElementById('campo_qtd_anos').style.display = val === 'Personalizado' ? '' : 'none';
    if (val !== 'Personalizado') document.getElementById('edit_qtd_anos').value = '';
}

function iconeAnexo(ext) {
    if (['png','jpg','jpeg'].includes(ext)) return 'fa-file-image text-warning';
    if (ext === 'pdf') return 'fa-file-pdf text-danger';
    if (['doc','docx'].includes(ext)) return 'fa-file-word text-primary';
    return 'fa-file text-info';
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function verAnexos(g) {
    document.getElementById('modalAnexosTitle').innerHTML = '<i class="fas fa-paperclip me-2"></i>Anexos — ' + escHtml(g.nome_equipamento);
    var body = document.getElementById('modalAnexosBody');
    if (!g._anexos || g._anexos.length === 0) {
        body.innerHTML = '<p class="text-secondary">Nenhum anexo cadastrado.</p>';
    } else {
        var html = '<ul class="list-group list-group-flush">';
        g._anexos.forEach(function(a) {
            var ext = a.nome_arquivo.split('.').pop().toLowerCase();
            html += '<li class="list-group-item bg-transparent text-white border-secondary d-flex justify-content-between align-items-center py-2">'
                + '<span><i class="fas ' + iconeAnexo(ext) + ' me-2"></i>' + escHtml(a.nome_original) + '</span>'
                + '<div class="d-flex gap-2">'
                + '<a href="' + APP_URL + '/pages/download_garantia_anexo.php?id=' + a.id + '" target="_blank" class="btn btn-sm btn-outline-info"><i class="fas fa-eye me-1"></i>Visualizar</a>'
                + '<a href="' + APP_URL + '/pages/download_garantia_anexo.php?id=' + a.id + '&download=1" class="btn btn-sm btn-outline-success"><i class="fas fa-download me-1"></i>Baixar</a>'
                + '</div>'
                + '</li>';
        });
        html += '</ul>';
        body.innerHTML = html;
    }
    new bootstrap.Modal(document.getElementById('modalVisualizarAnexos')).show();
}

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
    document.getElementById('edit_tipo_garantia').value = g.tipo_garantia || '';
    toggleQtdAnos(g.tipo_garantia || '');
    document.getElementById('edit_qtd_anos').value = g.qtd_anos || '';

    // Anexos existentes
    var container = document.getElementById('lista_anexos_container');
    var lista = document.getElementById('lista_anexos');
    lista.innerHTML = '';
    if (g._anexos && g._anexos.length > 0) {
        g._anexos.forEach(function(a) {
            var li = document.createElement('li');
            li.className = 'list-group-item bg-transparent text-white border-secondary d-flex justify-content-between align-items-center py-1';
            li.innerHTML = '<span><i class="fas fa-file me-2 text-info"></i>' + escHtml(a.nome_original) + '</span>'
                + '<div class="d-flex gap-1">'
                + '<a href="' + APP_URL + '/pages/download_garantia_anexo.php?id=' + a.id + '" target="_blank" class="btn btn-sm btn-outline-info py-0 px-2" title="Visualizar"><i class="fas fa-eye"></i></a>'
                + '<a href="' + APP_URL + '/pages/download_garantia_anexo.php?id=' + a.id + '&download=1" class="btn btn-sm btn-outline-success py-0 px-2" title="Baixar"><i class="fas fa-download"></i></a>'
                + '<a href="?acao=excluir_anexo&id=' + a.id + '" class="btn btn-sm btn-outline-danger py-0 px-2" title="Remover"'
                + ' onclick="return confirm(\'Remover este anexo?\')"><i class="fas fa-times"></i></a>'
                + '</div>';
            lista.appendChild(li);
        });
        container.style.display = '';
    } else {
        container.style.display = 'none';
    }

    new bootstrap.Modal(document.getElementById('modalGarantia')).show();
}

// Resetar modal ao abrir via botão "Nova Garantia"
document.querySelector('[data-bs-target="#modalGarantia"]').addEventListener('click', function() {
    document.getElementById('modalTitle').innerText = 'Cadastrar Nova Garantia';
    document.getElementById('edit_id').value = '';
    document.getElementById('edit_nome_equipamento').value = '';
    document.getElementById('edit_numero_serie').value = '';
    document.getElementById('edit_data_compra').value = '';
    document.getElementById('edit_expira_garantia').value = '';
    document.getElementById('edit_fornecedor').value = '';
    document.getElementById('edit_responsavel').value = '';
    document.getElementById('edit_observacoes').value = '';
    document.getElementById('edit_tipo_garantia').value = '';
    document.getElementById('edit_qtd_anos').value = '';
    document.getElementById('campo_qtd_anos').style.display = 'none';
    document.getElementById('edit_anexos').value = '';
    document.getElementById('lista_anexos_container').style.display = 'none';
    document.getElementById('lista_anexos').innerHTML = '';
});
</script>

<?php include '../includes/footer.php'; ?>
