<?php
// Funções de Utilitários

function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}

function calcularDiasRestantes($data_fim) {
    $hoje = new DateTime();
    $fim = new DateTime($data_fim);
    $intervalo = $hoje->diff($fim);
    return (int)$intervalo->format('%r%a');
}

function getStatusColor($dias) {
    if ($dias < 0) return 'expired';
    if ($dias <= 30) return 'expiring';
    return 'active';
}

function getStatusLabel($status) {
    switch ($status) {
        case 'expired': return 'Vencido';
        case 'expiring': return 'Vencendo em breve';
        case 'active': return 'Ativo';
        default: return 'Desconhecido';
    }
}

function formatarTipoContrato($tipo, $qtd_anos) {
    if ($tipo === 'Personalizado' && $qtd_anos) {
        return $qtd_anos . ' ' . ($qtd_anos == 1 ? 'Ano' : 'Anos');
    }
    return $tipo;
}

function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] === 'admin';
}

function isGestor() {
    return isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] === 'gestor';
}

function isVisualizador() {
    return isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] === 'visualizador';
}

function canEdit() {
    return isAdmin() || isGestor();
}

function getPerfilLabel($perfil) {
    $labels = [
        'admin'        => 'Administrador',
        'gestor'       => 'Gestor',
        'visualizador' => 'Visualizador',
    ];
    return $labels[$perfil] ?? ucfirst($perfil);
}

function getPerfilBadgeClass($perfil) {
    $classes = [
        'admin'        => 'bg-info text-dark',
        'gestor'       => 'bg-primary',
        'visualizador' => 'bg-secondary',
    ];
    return $classes[$perfil] ?? 'bg-secondary';
}
?>
