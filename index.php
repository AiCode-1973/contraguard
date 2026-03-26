<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
verificarLogin();

// Lógica de Alertas (Simplificada para rodar no load)
function atualizarAlertas($pdo) {
    // 1. Atualizar status de contratos
    $pdo->exec("UPDATE contratos SET status = 'expired' WHERE data_fim < CURDATE()");
    $pdo->exec("UPDATE contratos SET status = 'expiring' WHERE data_fim >= CURDATE() AND data_fim <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    $pdo->exec("UPDATE contratos SET status = 'active' WHERE data_fim > DATE_ADD(CURDATE(), INTERVAL 30 DAY)");

    // 2. Atualizar status de garantias
    $pdo->exec("UPDATE garantias SET status = 'expired' WHERE expira_garantia < CURDATE()");
    $pdo->exec("UPDATE garantias SET status = 'expiring' WHERE expira_garantia >= CURDATE() AND expira_garantia <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    $pdo->exec("UPDATE garantias SET status = 'active' WHERE expira_garantia > DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
}

atualizarAlertas($pdo);

// Buscar métricas
$total_contratos = $pdo->query("SELECT COUNT(*) FROM contratos")->fetchColumn();
$vencendo_breve = $pdo->query("SELECT COUNT(*) FROM contratos WHERE status = 'expiring'")->fetchColumn() + 
                  $pdo->query("SELECT COUNT(*) FROM garantias WHERE status = 'expiring'")->fetchColumn();
$vencidos = $pdo->query("SELECT COUNT(*) FROM contratos WHERE status = 'expired'")->fetchColumn() + 
            $pdo->query("SELECT COUNT(*) FROM garantias WHERE status = 'expired'")->fetchColumn();

// Buscar itens recentes
$alertas_recentes = $pdo->query("
    (SELECT 'Contrato' as tipo, nome, data_fim as data, status FROM contratos)
    UNION
    (SELECT 'Garantia' as tipo, nome_equipamento as nome, expira_garantia as data, status FROM garantias)
    ORDER BY data ASC LIMIT 6
")->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Dashboard Geral</h2>
        <p class="text-secondary small">Visão em tempo real dos seus contratos e garantias.</p>
    </div>
    <div class="text-end">
        <span class="badge bg-info p-2">Bem-vindo, <?php echo $_SESSION['usuario_nome']; ?></span>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 text-center">
            <p class="card-title">Total de Itens</p>
            <p class="card-value"><?php echo $total_contratos; ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 text-center border-warning">
            <p class="card-title text-warning">Vencendo em Breve</p>
            <p class="card-value text-warning"><?php echo $vencendo_breve; ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 text-center border-danger">
            <p class="card-title text-danger">Vencidos</p>
            <p class="card-value text-danger"><?php echo $vencidos; ?></p>
        </div>
    </div>
</div>

<!-- Recent Items -->
<div class="card mb-4">
    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center p-3">
        <h5 class="m-0">Próximos Vencimentos</h5>
        <a href="pages/contratos.php" class="btn btn-sm btn-outline-info">Ver Todos</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3 text-secondary">Tipo</th>
                        <th class="text-secondary">Nome</th>
                        <th class="text-secondary">Data Fim</th>
                        <th class="text-secondary">Status</th>
                        <th class="pe-3 text-secondary">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alertas_recentes)): ?>
                        <tr><td colspan="5" class="text-center p-4 text-secondary">Nenhum item cadastrado.</td></tr>
                    <?php else: ?>
                        <?php foreach($alertas_recentes as $item): 
                            $dias = calcularDiasRestantes($item['data']);
                            $color = $item['status'];
                        ?>
                        <tr class="<?php echo $color; ?>">
                            <td class="ps-3"><span class="badge bg-secondary"><?php echo $item['tipo']; ?></span></td>
                            <td><strong><?php echo $item['nome']; ?></strong></td>
                            <td><?php echo formatarData($item['data']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $color; ?>">
                                    <?php echo getStatusLabel($item['status']); ?>
                                </span>
                            </td>
                            <td class="pe-3">
                                <a href="#" class="btn btn-sm btn-outline-light"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
