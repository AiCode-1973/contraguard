<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
verificarLogin();
if (!isAdmin()) { header('Location: ' . APP_URL . '/index.php'); exit; }

// ── Migrações ─────────────────────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS setores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao VARCHAR(255) DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$colunas = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('nome', $colunas))
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN nome VARCHAR(100) DEFAULT NULL AFTER id");
if (!in_array('email', $colunas))
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN email VARCHAR(150) DEFAULT NULL");
if (!in_array('setor_id', $colunas))
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN setor_id INT DEFAULT NULL");

// Migrar valores antigos: 'user' → 'gestor'
$pdo->exec("UPDATE usuarios SET nivel='gestor' WHERE nivel='user'");

// ── Setores para select ───────────────────────────────────────────────────
$setores = $pdo->query("SELECT id, nome FROM setores ORDER BY nome ASC")->fetchAll();

$msg = '';
$msg_tipo = 'success';

// ── Excluir ───────────────────────────────────────────────────────────────
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id_excluir = (int)$_GET['id'];
    if ($id_excluir === (int)$_SESSION['usuario_id']) {
        $msg = "Você não pode excluir sua própria conta.";
        $msg_tipo = 'danger';
    } else {
        $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id_excluir]);
        header('Location: usuarios.php?msg=Usu%C3%A1rio+exclu%C3%ADdo+com+sucesso&tipo=success');
        exit;
    }
}

// ── POST ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao_form = $_POST['acao_form'] ?? '';
    $nome      = trim($_POST['nome'] ?? '');
    $usuario   = trim($_POST['usuario'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $perfil    = $_POST['perfil'] ?? 'gestor';
    $setor_id  = (int)($_POST['setor_id'] ?? 0) ?: null;
    $id        = (int)($_POST['id'] ?? 0);

    if (!in_array($perfil, ['admin', 'gestor', 'visualizador'])) $perfil = 'gestor';

    if ($acao_form === 'criar') {
        $senha     = $_POST['senha'] ?? '';
        $confirmar = $_POST['confirmar_senha'] ?? '';
        if (!$usuario) {
            $msg = "O nome de usuário é obrigatório."; $msg_tipo = 'danger';
        } elseif (strlen($senha) < 6) {
            $msg = "A senha deve ter no mínimo 6 caracteres."; $msg_tipo = 'danger';
        } elseif ($senha !== $confirmar) {
            $msg = "As senhas não coincidem."; $msg_tipo = 'danger';
        } else {
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
            $check->execute([$usuario]);
            if ($check->fetch()) {
                $msg = "Este nome de usuário já está em uso."; $msg_tipo = 'danger';
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO usuarios (nome, usuario, email, senha, nivel, setor_id) VALUES (?, ?, ?, ?, ?, ?)")
                    ->execute([$nome ?: null, $usuario, $email ?: null, $hash, $perfil, $setor_id]);
                header('Location: usuarios.php?msg=Usu%C3%A1rio+criado+com+sucesso&tipo=success'); exit;
            }
        }
    } elseif ($acao_form === 'editar') {
        if (!$usuario) {
            $msg = "O nome de usuário é obrigatório."; $msg_tipo = 'danger';
        } else {
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
            $check->execute([$usuario, $id]);
            if ($check->fetch()) {
                $msg = "Este nome de usuário já está em uso."; $msg_tipo = 'danger';
            } else {
                $pdo->prepare("UPDATE usuarios SET nome=?, usuario=?, email=?, nivel=?, setor_id=? WHERE id=?")
                    ->execute([$nome ?: null, $usuario, $email ?: null, $perfil, $setor_id, $id]);
                header('Location: usuarios.php?msg=Usu%C3%A1rio+atualizado+com+sucesso&tipo=success'); exit;
            }
        }
    } elseif ($acao_form === 'senha') {
        $nova      = $_POST['nova_senha'] ?? '';
        $confirmar = $_POST['confirmar_nova_senha'] ?? '';
        if (strlen($nova) < 6) {
            $msg = "A nova senha deve ter no mínimo 6 caracteres."; $msg_tipo = 'danger';
        } elseif ($nova !== $confirmar) {
            $msg = "As senhas não coincidem."; $msg_tipo = 'danger';
        } else {
            $pdo->prepare("UPDATE usuarios SET senha=? WHERE id=?")
                ->execute([password_hash($nova, PASSWORD_DEFAULT), $id]);
            header('Location: usuarios.php?msg=Senha+redefinida+com+sucesso&tipo=success'); exit;
        }
    }
}

if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
    $msg_tipo = htmlspecialchars($_GET['tipo'] ?? 'success');
}

$usuarios = $pdo->query(
    "SELECT u.*, s.nome as setor_nome
     FROM usuarios u
     LEFT JOIN setores s ON s.id = u.setor_id
     ORDER BY u.id ASC"
)->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-users-gear me-2 text-info"></i>Gerenciamento de Usuários</h4>
        <p class="text-secondary small mb-0">Perfis: Administrador, Gestor e Visualizador</p>
    </div>
    <button class="btn btn-info fw-bold px-4" id="btnNovoUsuario" data-bs-toggle="modal" data-bs-target="#modalUsuario">
        <i class="fas fa-plus me-2"></i>Novo Usuário
    </button>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show">
    <?php echo $msg; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card border-0 rounded-4 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">#</th>
                    <th>Nome / Usuário</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Setor</th>
                    <th class="pe-4 text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td class="ps-4 text-secondary"><?php echo $u['id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($u['nome'] ?: $u['usuario']); ?></strong>
                        <?php if ($u['nome']): ?><div class="small text-secondary">@<?php echo htmlspecialchars($u['usuario']); ?></div><?php endif; ?>
                    </td>
                    <td class="text-secondary"><?php echo htmlspecialchars($u['email'] ?: '—'); ?></td>
                    <td><span class="badge <?php echo getPerfilBadgeClass($u['nivel']); ?> fw-bold"><?php echo getPerfilLabel($u['nivel']); ?></span></td>
                    <td><?php echo htmlspecialchars($u['setor_nome'] ?? '—'); ?></td>
                    <td class="pe-4 text-center">
                        <button class="btn btn-sm btn-outline-info me-1" title="Editar"
                            onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES); ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning me-1" title="Redefinir senha"
                            onclick="redefinirSenha(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['usuario'], ENT_QUOTES); ?>')">
                            <i class="fas fa-key"></i>
                        </button>
                        <?php if ((int)$u['id'] === (int)$_SESSION['usuario_id']): ?>
                            <button class="btn btn-sm btn-outline-danger" disabled title="Não pode excluir sua própria conta"><i class="fas fa-trash"></i></button>
                        <?php else: ?>
                            <a href="?acao=excluir&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" title="Excluir"
                               onclick="return confirm('Excluir o usuário \'<?php echo htmlspecialchars($u['usuario'], ENT_QUOTES); ?>\'?')">
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

<!-- Modal Criar / Editar -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST">
                <input type="hidden" name="acao_form" id="acao_form_usuario" value="criar">
                <input type="hidden" name="id" id="edit_usuario_id">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title fw-bold" id="modalUsuarioTitle"><i class="fas fa-user-plus me-2 text-info"></i>Novo Usuário</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nome Completo</label>
                            <input type="text" name="nome" id="edit_nome_usuario" class="form-control" placeholder="Ex: Demetrius Silva">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Usuário (login) <span class="text-danger">*</span></label>
                            <input type="text" name="usuario" id="edit_usuario" class="form-control" placeholder="Ex: dema" required autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">E-mail</label>
                            <input type="email" name="email" id="edit_email_usuario" class="form-control" placeholder="Ex: dema@empresa.com">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold"><i class="fas fa-shield-halved me-2"></i>Perfil <span class="text-danger">*</span></label>
                            <select name="perfil" id="edit_perfil_usuario" class="form-select">
                                <option value="admin">Administrador</option>
                                <option value="gestor" selected>Gestor</option>
                                <option value="visualizador">Visualizador</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold"><i class="fas fa-building me-2"></i>Setor</label>
                            <select name="setor_id" id="edit_setor_usuario" class="form-select">
                                <option value="">— Sem setor —</option>
                                <?php foreach ($setores as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="campos_senha" class="col-12">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Senha <span class="text-danger">*</span></label>
                                    <input type="password" name="senha" id="edit_senha" class="form-control" placeholder="Mínimo 6 caracteres" autocomplete="new-password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Confirmar Senha <span class="text-danger">*</span></label>
                                    <input type="password" name="confirmar_senha" id="edit_confirmar_senha" class="form-control" placeholder="Repita a senha">
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 rounded-3" style="background:rgba(0,223,216,0.05); border:1px solid rgba(0,223,216,0.1)">
                                <p class="small mb-1 fw-bold text-info"><i class="fas fa-circle-info me-1"></i>Descrição dos perfis:</p>
                                <ul class="small mb-0 text-secondary">
                                    <li><strong class="text-info">Administrador</strong> — Acesso total ao sistema.</li>
                                    <li><strong class="text-primary">Gestor</strong> — Cria e gerencia apenas os contratos sob sua responsabilidade.</li>
                                    <li><strong class="text-secondary">Visualizador</strong> — Visualiza apenas os contratos vinculados a ele.</li>
                                </ul>
                            </div>
                        </div>
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

<!-- Modal Redefinir Senha -->
<div class="modal fade" id="modalSenha" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST">
                <input type="hidden" name="acao_form" value="senha">
                <input type="hidden" name="id" id="senha_usuario_id">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title fw-bold" id="modalSenhaTitle"><i class="fas fa-key me-2 text-warning"></i>Redefinir Senha</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nova Senha <span class="text-danger">*</span></label>
                            <input type="password" name="nova_senha" class="form-control" placeholder="Mínimo 6 caracteres" required autocomplete="new-password">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Confirmar Nova Senha <span class="text-danger">*</span></label>
                            <input type="password" name="confirmar_nova_senha" class="form-control" placeholder="Repita a nova senha" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning fw-bold px-4 text-dark"><i class="fas fa-key me-2"></i>Redefinir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('btnNovoUsuario').addEventListener('click', function () {
    document.getElementById('modalUsuarioTitle').innerHTML = '<i class="fas fa-user-plus me-2 text-info"></i>Novo Usuário';
    document.getElementById('acao_form_usuario').value = 'criar';
    document.getElementById('edit_usuario_id').value = '';
    document.getElementById('edit_nome_usuario').value = '';
    document.getElementById('edit_usuario').value = '';
    document.getElementById('edit_email_usuario').value = '';
    document.getElementById('edit_perfil_usuario').value = 'gestor';
    document.getElementById('edit_setor_usuario').value = '';
    document.getElementById('edit_senha').value = '';
    document.getElementById('edit_confirmar_senha').value = '';
    document.getElementById('edit_senha').required = true;
    document.getElementById('edit_confirmar_senha').required = true;
    document.getElementById('campos_senha').style.display = '';
    document.getElementById('modal_btn_salvar').innerHTML = '<i class="fas fa-save me-2"></i>Salvar';
});

function editarUsuario(u) {
    document.getElementById('modalUsuarioTitle').innerHTML = '<i class="fas fa-user-edit me-2 text-info"></i>Editar Usuário';
    document.getElementById('acao_form_usuario').value = 'editar';
    document.getElementById('edit_usuario_id').value = u.id;
    document.getElementById('edit_nome_usuario').value = u.nome || '';
    document.getElementById('edit_usuario').value = u.usuario;
    document.getElementById('edit_email_usuario').value = u.email || '';
    document.getElementById('edit_perfil_usuario').value = u.nivel;
    document.getElementById('edit_setor_usuario').value = u.setor_id || '';
    document.getElementById('campos_senha').style.display = 'none';
    document.getElementById('edit_senha').required = false;
    document.getElementById('edit_confirmar_senha').required = false;
    document.getElementById('modal_btn_salvar').innerHTML = '<i class="fas fa-save me-2"></i>Atualizar';
    new bootstrap.Modal(document.getElementById('modalUsuario')).show();
}

function redefinirSenha(id, nomeUsuario) {
    document.getElementById('senha_usuario_id').value = id;
    document.getElementById('modalSenhaTitle').innerHTML = '<i class="fas fa-key me-2 text-warning"></i>Redefinir Senha — ' + nomeUsuario;
    new bootstrap.Modal(document.getElementById('modalSenha')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
