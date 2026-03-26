// Dados Mock para o Dashboard ContraGuard
const contracts = [
    {
        id: 1,
        title: "Microsoft 365 Enterprise",
        category: "Software",
        department: "TI",
        expirationDate: "24 Out, 2026",
        daysRemaining: -5, // Expirado
        progress: 100,
        status: "expired"
    },
    {
        id: 2,
        title: "Manutenção de HVAC - Torre A",
        category: "Infraestrutura",
        department: "Operações",
        expirationDate: "15 Abr, 2026",
        daysRemaining: 18, // Vencendo em breve
        progress: 85,
        status: "expiring"
    },
    {
        id: 3,
        title: "Garantia Servidor Dell",
        category: "Hardware",
        department: "TI",
        expirationDate: "30 Dez, 2027",
        daysRemaining: 642, // Ativo
        progress: 30,
        status: "active"
    },
    {
        id: 4,
        title: "Licença Firewall Cisco",
        category: "Software",
        department: "TI",
        expirationDate: "10 Mai, 2026",
        daysRemaining: 45, // Vencendo em breve
        progress: 75,
        status: "expiring"
    },
    {
        id: 5,
        title: "Serviços de Limpeza",
        category: "Infraestrutura",
        department: "RH",
        expirationDate: "01 Jan, 2027",
        daysRemaining: 280, // Ativo
        progress: 40,
        status: "active"
    },
    {
        id: 6,
        title: "Suporte Banco de Dados Oracle",
        category: "Software",
        department: "TI",
        expirationDate: "20 Mar, 2026",
        daysRemaining: -2, // Expirado
        progress: 100,
        status: "expired"
    }
];

function renderDashboard() {
    const grid = document.getElementById('dashboard-grid');
    grid.innerHTML = '';

    contracts.forEach(contract => {
        const remainingText = contract.daysRemaining < 0 
            ? `${Math.abs(contract.daysRemaining)} dias em atraso`
            : `${contract.daysRemaining} dias restantes`;
        
        const card = document.createElement('div');
        card.className = 'card';
        
        card.innerHTML = `
            <div class="card-status-indicator ${contract.status}"></div>
            <div class="card-header">
                <h3 class="card-title">${contract.title}</h3>
                <span class="card-tag">${contract.category}</span>
            </div>
            <div class="card-details">
                <div class="detail-item">
                    <span class="detail-label">Departamento</span>
                    <span class="detail-value">${contract.department}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Data de Renovação</span>
                    <span class="detail-value">${contract.expirationDate}</span>
                </div>
            </div>
            <div class="progress-container">
                <div class="progress-label">
                    <span>${remainingText}</span>
                    <span>${contract.progress}% utilizado</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill ${contract.status}" style="width: ${contract.progress}%"></div>
                </div>
            </div>
        `;
        
        grid.appendChild(card);
    });
}

// Renderização inicial
document.addEventListener('DOMContentLoaded', renderDashboard);

// Lógica mock de filtragem
document.querySelectorAll('.filter-item').forEach(item => {
    item.addEventListener('click', () => {
        document.querySelectorAll('.filter-item').forEach(i => i.classList.remove('active'));
        item.classList.add('active');
    });
});
