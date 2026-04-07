<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
verificarLogin();

// Somente admin pode acessar
if (!isAdmin()) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

// Migração automática: adiciona colunas nome e email se não existirem
$colunas = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('nome', $colunas)) {
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN nome VARCHAR(100) DEFAULT NULL AFTER id");
}
if (!in_array('email', $colunas)) {
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN email VARCHAR(150) DEFAULT NULL");
}

$msg = '';
$msg_tipo = 'success';

// Excluir usuário
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id_excluir = (int)$_GET['id'];
    if ($id_excluir === (int)$_SESSION['usuario_id']) {
        $msg = "Você não pode excluir sua própria conta.";
        $msg_tipo = 'danger';
    } else {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id_excluir]);
        header('Location: usuarios.php?msg=Usuário excluído com sucesso&tipo=success');
        exit;
    }
}

// Processar formulário POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao_form = $_POST['acao_form'] ?? '';

    if ($acao_form === 'criar') {
        $nome      = trim($_POST['nome'] ?? '');
        $usuario   = trim($_POST['usuario'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $nivel     = $_POST['nivel'] ?? 'user';
        $senha     = $_POST['senha'] ?? '';
        $confirmar = $_POST['confirmar_senha'] ?? '';

        if (!$usuario) {
            $msg = "O nome de usuário é obrigatório.";
            $msg_tipo = 'danger';
        } elseif (strlen($senha) < 6) {
            $msg = "A senha deve ter no mínimo 6 caracteres.";
            $msg_tipo = 'danger';
        } elseif ($senha !== $confirmar) {
            $msg = "As senhas não coincidem.";
            $msg_tipo = 'danger';
        } else {
            // Verificar unicidade
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
            $check->execute([$usuario]);
            if ($check->fetch()) {
                $msg = "Este nome de usuário já está em uso.";
                $msg_tipo = 'danger';
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, usuario, email, senha, nivel) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nome ?: null, $usuario, $email ?: null, $hash, $nivel]);
                header('Location: usuarios.php?msg=Usuário criado com sucesso&tipo=success');
                exit;
            }
        }

    } elseif ($acao_form === 'editar') {
        $id      = (int)($_POST['id'] ?? 0);
        $nome    = trim($_POST['nome'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $nivel   = $_POST['nivel'] ?? 'user';

        if (!$usuario) {
            $msg = "O nome de usuário é obrigatório.";
            $msg_tipo = 'danger';
        } else {
            // Verificar unicidade excluindo o próprio registro
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
            $check->execute([$usuario, $id]);
            if ($check->fetch()) {
                $msg = "Este nome de usuário já está em uso por outro usuário.";
                $msg_tipo = 'danger';
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, usuario=?, email=?, nivel=? WHERE id=?");
                $stmt->execute([$nome ?: null, $usuario, $email ?: null, $nivel, $id]);
                header('Location: usuarios.php?msg=Usuário atualizado com sucesso&tipo=success');
                exit;
            }
        }

    } elseif ($acao_form === 'senha') {
        $id        = (int)($_POST['id'] ?? 0);
        $nova      = $_POST['nova_senha'] ?? '';
        $confirmar = $_POST['confirmar_nova_senha'] ?? '';

        if (strlen($nova) < 6) {
            $msg = "A nova senha deve ter no mínimo 6 caracteres.";
            $msg_tipo = 'danger';
        } elseif ($nova !== $confirmar) {
            $msg = "As senhas não coincidem.";
            $msg_tipo = 'danger';
        } else {
            $hash = password_hash($nova, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET senha=? WHERE id=?");
            $stmt->execute([$hash, $id]);
            header('Location: usuarios.php?msg=Senha redefinida com sucesso&tipo=success');
            exit;
        }
    }
}

// Mensagens via GET
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
    $msg_tipo = htmlspecialchars($_GET['tipo'] ?? 'success');
}

$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY id ASC")->fetchAll();

include '../includes/header.php';
?>

<div class="card bg-navy border-0 rounded-4 shadow-sm mt-4">
    <div class="card-header bg-transparent border-bottom border-secondary d-flex justify-content-between align-items-center p-4">
        <h4 class="m-0 fw-bold text-white"><i class="fas fa-users-gear me-2 text-info"></i>Gerenciamento de Usuários</h4>
        <button class="btn btn-info fw-bold px-4" id="btnNovoUsuario" data-bs-toggle="modal" data-bs-target="#modalUsuario">
            <i class="fas fa-plus me-2"></i> Novo Usuário
        </button>
    </div>
    <div class="card-body p-0">
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg_tipo; ?> m-3"><?php echo $msg; ?></div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-hover table-dark-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Nome</th>
                        <th>Usuário</th>
                        <th>E-mail</th>
                        <th>Nível</th>
                        <th class="pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td class="ps-4 text-secondary"><?php echo $u['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['nome'] ?: '—'); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['usuario']); ?></td>
                        <td class="text-secondary"><?php echo htmlspecialchars($u['email'] ?: '—'); ?></td>
                        <td>
                            <?php if ($u['nivel'] === 'admin'): ?>
                                <span class="badge bg-info text-dark fw-bold">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Usuário</span>
                            <?php endif; ?>
                        </td>
                        <td class="pe-4">
                            <button class="btn btn-sm btn-outline-info" title="Editar"
                                onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning" title="Redefinir senha"
                                onclick="redefinirSenha(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['usuario'], ENT_QUOTES); ?>')">
                                <i class="fas fa-key"></i>
                            </button>
                            <?php if ((int)$u['id'] === (int)$_SESSION['usuario_id']): ?>
                                <button class="btn btn-sm btn-outline-danger" disabled title="Não é possível excluir sua própria conta">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php else: ?>
                                <a href="?acao=excluir&id=<?php echo $u['id']; ?>"
                                   class="btn btn-sm btn-outline-danger" title="Excluir"
                                   onclick="return confirm('Excluir o usuário \'<?php echo htmlspecialchars($u['usuario'], ENT_QUOTES); ?>\'? Esta ação não pode ser desfeita.')">
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

<!-- Modal Criar / Editar Usuário -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="acao_form" id="acao_form_usuario" value="criar">
                <input type="hidden" name="id" id="edit_usuario_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUsuarioTitle"><i class="fas fa-user-plus me-2"></i>Novo Usuário</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-id-card me-2"></i>Nome Completo</label>
                            <input type="text" name="nome" id="edit_nome_usuario" class="form-control" placeholder="Ex: Demetrius Silva">
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-user me-2"></i>Usuário (login) <span class="text-danger">*</span></label>
                            <input type="text" name="usuario" id="edit_usuario" class="form-control" placeholder="Ex: dema" required autocomplete="off">
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-envelope me-2"></i>E-mail</label>
                            <input type="email" name="email" id="edit_email_usuario" class="form-control" placeholder="Ex: dema@empresa.com">
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="form-label"><i class="fas fa-shield-halved me-2"></i>Nível de Acesso <span class="text-danger">*</span></label>
                            <select name="nivel" id="edit_nivel_usuario" class="form-select">
                                <option value="user">Usuário</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div id="campos_senha">
                            <div class="row g-4 px-2">
                                <div class="col-md-6 text-start">
                                    <label class="form-label"><i class="fas fa-lock me-2"></i>Senha <span class="text-danger">*</span></label>
                                    <input type="password" name="senha" id="edit_senha" class="form-control" placeholder="Mínimo 6 caracteres" autocomplete="new-password">
                                </div>
                                <div class="col-md-6 text-start">
                                    <label class="form-label"><i class="fas fa-lock me-2"></i>Confirmar Senha <span class="text-danger">*</span></label>
                                    <input type="password" name="confirmar_senha" id="edit_confirmar_senha" class="form-control" placeholder="Repita a senha">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info px-5 rounded-3 fw-bold shadow-sm">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Redefinir Senha -->
<div class="modal fade" id="modalSenha" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="acao_form" value="senha">
                <input type="hidden" name="id" id="senha_usuario_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSenhaTitle"><i class="fas fa-key me-2"></i>Redefinir Senha</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-12 text-start">
                            <label class="form-label"><i class="fas fa-lock me-2"></i>Nova Senha <span class="text-danger">*</span></label>
                            <input type="password" name="nova_senha" class="form-control" placeholder="Mínimo 6 caracteres" required autocomplete="new-password">
                        </div>
                        <div class="col-12 text-start">
                            <label class="form-label"><i class="fas fa-lock me-2"></i>Confirmar Nova Senha <span class="text-danger">*</span></label>
                            <input type="password" name="confirmar_nova_senha" class="form-control" placeholder="Repita a nova senha" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning px-5 rounded-3 fw-bold shadow-sm text-dark">Redefinir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Ao abrir o modal pelo botão "Novo Usuário", garante modo criação
document.getElementById('btnNovoUsuario').addEventListener('click', function () {
    document.getElementById('modalUsuarioTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>Novo Usuário';
    document.getElementById('acao_form_usuario').value = 'criar';
    document.getElementById('edit_usuario_id').value = '';
    document.getElementById('edit_nome_usuario').value = '';
    document.getElementById('edit_usuario').value = '';
    document.getElementById('edit_email_usuario').value = '';
    document.getElementById('edit_nivel_usuario').value = 'user';
    document.getElementById('edit_senha').value = '';
    document.getElementById('edit_confirmar_senha').value = '';
    document.getElementById('edit_senha').required = true;
    document.getElementById('edit_confirmar_senha').required = true;
    document.getElementById('campos_senha').style.display = '';
});

function editarUsuario(u) {
    document.getElementById('modalUsuarioTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i>Editar Usuário';
    document.getElementById('acao_form_usuario').value = 'editar';
    document.getElementById('edit_usuario_id').value = u.id;
    document.getElementById('edit_nome_usuario').value = u.nome || '';
    document.getElementById('edit_usuario').value = u.usuario;
    document.getElementById('edit_email_usuario').value = u.email || '';
    document.getElementById('edit_nivel_usuario').value = u.nivel;
    // Esconde e remove required dos campos de senha no modo editar
    document.getElementById('campos_senha').style.display = 'none';
    document.getElementById('edit_senha').required = false;
    document.getElementById('edit_confirmar_senha').required = false;
    document.getElementById('edit_senha').value = '';
    document.getElementById('edit_confirmar_senha').value = '';
    var modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
    modal.show();
}

function redefinirSenha(id, nomeUsuario) {
    document.getElementById('senha_usuario_id').value = id;
    document.getElementById('modalSenhaTitle').innerHTML = '<i class="fas fa-key me-2"></i>Redefinir Senha — ' + nomeUsuario;
    var modal = new bootstrap.Modal(document.getElementById('modalSenha'));
    modal.show();
}
</script>

<?php include '../includes/footer.php'; ?>
