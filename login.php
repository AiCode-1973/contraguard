<?php
require_once 'includes/db.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if ($usuario && $senha) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nome'] = $user['usuario'];
            $_SESSION['usuario_nivel'] = $user['nivel'];
            header('Location: index.php');
            exit;
        } else {
            $erro = "Usuário ou senha inválidos.";
        }
    } else {
        $erro = "Por favor, preencha todos os campos.";
    }
}

include 'includes/header.php';
?>

<div class="login-container">
    <div class="card p-4">
        <div class="text-center mb-4">
            <i class="fas fa-shield-alt fa-3x text-info"></i>
            <h2 class="mt-2">ContraGuard</h2>
            <p class="text-secondary">Faça login para continuar</p>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Usuário</label>
                <input type="text" name="usuario" class="form-control bg-dark text-white border-secondary" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label">Senha</label>
                <input type="password" name="senha" class="form-control bg-dark text-white border-secondary" required>
            </div>
            <button type="submit" class="btn btn-info w-100 fw-bold">Entrar</button>
        </form>
        
        <div class="mt-3 text-center">
            <small class="text-secondary">Dica: admin / admin123</small>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
