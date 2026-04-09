<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
verificarLogin();

$msg  = '';
$tipo = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $atual     = $_POST['senha_atual'] ?? '';
    $nova      = $_POST['nova_senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    // Busca a senha atual no banco
    $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($atual, $user['senha'])) {
        $msg  = 'A senha atual está incorreta.';
        $tipo = 'danger';
    } elseif (strlen($nova) < 6) {
        $msg  = 'A nova senha deve ter no mínimo 6 caracteres.';
        $tipo = 'danger';
    } elseif ($nova !== $confirmar) {
        $msg  = 'A confirmação não é igual à nova senha.';
        $tipo = 'danger';
    } else {
        $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")
            ->execute([password_hash($nova, PASSWORD_DEFAULT), $_SESSION['usuario_id']]);
        $msg  = 'Senha alterada com sucesso!';
        $tipo = 'success';
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card border-0 rounded-4 shadow-sm">
            <div class="card-header bg-transparent border-bottom border-secondary p-4 pb-3">
                <h5 class="fw-bold mb-0"><i class="fas fa-lock me-2 text-info"></i>Alterar Senha</h5>
                <p class="text-secondary small mb-0">Usuário: <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></p>
            </div>
            <div class="card-body p-4">
                <?php if ($msg): ?>
                <div class="alert alert-<?php echo $tipo; ?> alert-dismissible fade show mb-4">
                    <?php echo htmlspecialchars($msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Senha Atual <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="senha_atual" id="senha_atual" class="form-control" placeholder="Digite sua senha atual" required autocomplete="current-password">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleSenha('senha_atual', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nova Senha <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="nova_senha" id="nova_senha" class="form-control" placeholder="Mínimo 6 caracteres" required autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleSenha('nova_senha', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Confirmar Nova Senha <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" placeholder="Repita a nova senha" required autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleSenha('confirmar_senha', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-info fw-bold py-2">
                            <i class="fas fa-save me-2"></i>Salvar Nova Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSenha(id, btn) {
    var input = document.getElementById(id);
    var icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
