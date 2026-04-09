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
            $_SESSION['usuario_id']     = $user['id'];
            $_SESSION['usuario_nome']   = !empty($user['nome']) ? $user['nome'] : $user['usuario'];
            $_SESSION['usuario_nivel']  = $user['nivel']; // legado
            $_SESSION['usuario_perfil'] = $user['nivel']; // admin | gestor | visualizador
            $_SESSION['usuario_setor_id'] = $user['setor_id'] ?? null;
            header('Location: index.php');
            exit;
        } else {
            $erro = "Usuário ou senha inválidos.";
        }
    } else {
        $erro = "Por favor, preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ContraGuard | Acesso ao Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">

<div class="login-card">
    <div class="text-center mb-5">
        <div class="login-logo">
            <i class="fas fa-shield-halved"></i>
        </div>
        <h1 class="fw-bold h2 mb-1">ContraGuard</h1>
        <p class="text-secondary opacity-75">Advanced Asset Security</p>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger border-0 bg-danger bg-opacity-25 text-danger small mb-4 py-2 px-3 rounded-3">
            <i class="fas fa-exclamation-circle me-1"></i> <?php echo $erro; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="login-input-group">
            <i class="fas fa-user-shield"></i>
            <input type="text" name="usuario" placeholder="Nome de usuário" required autofocus autocomplete="off">
        </div>
        
        <div class="login-input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="senha" placeholder="Sua senha secreta" required>
        </div>

        <button type="submit" class="btn login-btn w-100 shadow-sm">
            Entrar no Sistema <i class="fas fa-chevron-right ms-2 small"></i>
        </button>
    </form>

    <div class="mt-5 text-center">
        <p class="text-secondary small mb-0">Protegido por criptografia 256-bit</p>
        <div class="mt-2 text-muted fw-bold" style="font-size: 0.65rem; opacity: 0.5;">
            DEMO: ADMIN / ADMIN123
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
