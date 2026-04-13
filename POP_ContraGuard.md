# POP — ContraGuard
**Procedimento Operacional Padrão**
**Setor de Tecnologia da Informação — Hospital**
Versão 2.0 | Abril de 2026

---

## 1. Identificação

| Campo | Informação |
|---|---|
| **Código do POP** | TI-CG-001 |
| **Sistema** | ContraGuard |
| **Setor responsável** | Tecnologia da Informação (TI) |
| **Finalidade** | Registro e controle de contratos e garantias de equipamentos de TI |
| **Público-alvo** | Colaboradores do setor de TI do hospital |
| **Elaborado por** | Equipe de TI |
| **Data de vigência** | Abril de 2026 |
| **Revisão** | Anual ou sempre que houver alteração no sistema |

---

## 2. Objetivo

Padronizar o registro, acompanhamento e controle de **contratos de serviços** e **garantias de equipamentos de TI** no sistema ContraGuard, garantindo rastreabilidade, conformidade e antecipação de vencimentos críticos no ambiente hospitalar.

---

## 3. Abrangência

Este POP aplica-se a todos os colaboradores do **setor de TI** do hospital que utilizam o sistema ContraGuard para:

- Registrar contratos de manutenção, licenças de software, suporte técnico e prestação de serviços de TI
- Registrar garantias de equipamentos (servidores, computadores, impressoras, switches, nobreaks, scanners, equipamentos médicos com interface de TI, entre outros)
- Consultar e acompanhar vencimentos e alertas

---

## 4. Responsabilidades

| Perfil no sistema | Cargo / Função no hospital | Responsabilidade |
|---|---|---|
| **Admin** | Analista ou Coordenador de TI | Gerenciar usuários, setores, categorias e todos os registros |
| **Gestor** | Técnico sênior / Supervisor de TI | Cadastrar, editar e excluir contratos e garantias do setor |
| **Visualizador** | Técnico de suporte / Estagiário | Consultar registros; não pode criar ou alterar dados |

---

## 5. Definições

| Termo | Definição no contexto hospitalar de TI |
|---|---|
| **Contrato** | Documento formal de prestação de serviços de TI (manutenção, suporte, licença de software, link de internet, outsourcing de impressão, etc.) |
| **Garantia** | Cobertura do fabricante ou fornecedor para equipamentos de TI (computadores, servidores, nobreaks, switches, impressoras, etc.) |
| **Categoria** | Classificação do registro (ex.: Infraestrutura, Software, Hardware, Telecom, Segurança, Impressão) |
| **Setor** | Agrupamento de usuários do sistema que compartilham a mesma visibilidade de dados (ex.: TI — Suporte, TI — Infraestrutura) |
| **Vencimento próximo** | Contrato ou garantia com prazo expirando nos próximos **60 dias** |
| **Vencido** | Contrato ou garantia com data de vencimento já ultrapassada |

---

## 6. Acesso ao Sistema

### 6.1 Login
1. Acesse o sistema pelo endereço fornecido pela equipe de TI
2. Informe seu **usuário** (login) e **senha**
3. Clique em **Entrar**

> Credenciais são fornecidas pelo administrador do sistema. Em caso de perda de acesso, contate o Coordenador de TI.

### 6.2 Encerramento de sessão
- Ao concluir o uso, clique no **avatar** (canto superior direito) → **Sair**
- Nunca deixe a sessão aberta em computadores compartilhados

### 6.3 Troca de senha
1. Clique no **avatar** → **Alterar Senha**
2. Informe a senha atual
3. Informe a nova senha (mínimo 6 caracteres)
4. Confirme e clique em **Salvar**

> Recomenda-se trocar a senha no primeiro acesso e a cada 90 dias.

---

## 7. Registro de Contratos de TI

### 7.1 Quando registrar
Registre um contrato sempre que o setor de TI do hospital firmar ou renovar:

- Contratos de **manutenção preventiva e corretiva** de equipamentos
- Contratos de **suporte técnico** com fornecedores
- **Licenças de software** (antivírus, sistemas hospitalares, pacote Office, etc.)
- Contratos de **link de internet** e conectividade
- Contratos de **outsourcing de impressão**
- Contratos de **locação de equipamentos**
- Acordos de **nível de serviço (SLA)** com prestadores

### 7.2 Como cadastrar um contrato (perfil Gestor ou Admin)
1. No menu lateral, clique em **Contratos**
2. Clique em **Novo Contrato**
3. Preencha os campos:

| Campo | Orientação de preenchimento |
|---|---|
| **Nome do contrato** | Identificação clara: ex. *"Contrato de Manutenção — Servidores Dell"* |
| **Fornecedor** | Razão social ou nome do prestador de serviço |
| **Responsável** | Nome do colaborador de TI responsável pelo acompanhamento |
| **Data de início** | Data de assinatura ou início de vigência |
| **Data de vencimento** | Data de encerramento ou renovação do contrato |
| **Valor** | Valor mensal ou total do contrato (opcional) |
| **Categoria** | Selecione a categoria adequada (ex.: Infraestrutura, Software, Telecom) |
| **Status** | Ativo / Inativo / Encerrado |
| **Observações** | Número do contrato, número de chamado, cláusulas relevantes (opcional) |

4. Clique em **Salvar**

### 7.3 Editar um contrato
1. Na listagem, localize o contrato (use a busca se necessário)
2. Clique no ícone de **lápis**
3. Altere os campos desejados e clique em **Salvar**

> Utilize a edição para atualizar o status ao término do contrato (ex.: mudar para *Encerrado*).

### 7.4 Excluir um contrato
1. Clique no ícone de **lixeira** na linha do contrato
2. Confirme na caixa de diálogo

> **Atenção:** A exclusão é permanente. Prefira alterar o status para *Encerrado* em vez de excluir, preservando o histórico.

---

## 8. Registro de Garantias de Equipamentos

### 8.1 Quando registrar
Registre uma garantia para todo equipamento de TI adquirido pelo hospital, incluindo:

- **Servidores** e storages
- **Computadores** (desktops, notebooks, all-in-ones)
- **Impressoras** e multifuncionais
- **Switches**, roteadores e access points
- **Nobreaks** (UPS)
- **Scanners** e leitores de código de barras
- **Monitores**
- **Equipamentos médicos** com componente de TI (terminais, tablets clínicos, etc.)
- **Periféricos críticos** (teclados industriais, leitores de cartão, etc.)

### 8.2 Como cadastrar uma garantia (perfil Gestor ou Admin)
1. No menu lateral, clique em **Garantias**
2. Clique em **Nova Garantia**
3. Preencha os campos:

| Campo | Orientação de preenchimento |
|---|---|
| **Nome** | Identificação do equipamento: ex. *"Servidor Dell PowerEdge R740 — UTI"* |
| **Fornecedor / Fabricante** | Nome do fabricante ou revendedor |
| **Número de série** | Número de série do equipamento (obrigatório para rastreabilidade) |
| **Data de início** | Data da nota fiscal ou data de ativação da garantia |
| **Data de vencimento** | Data de término da garantia conforme nota fiscal ou contrato |
| **Categoria** | Ex.: Servidor, Computador, Impressora, Nobreak, Rede |
| **Status** | Ativo / Inativo / Encerrado |
| **Observações** | Localização do equipamento no hospital, número NF, patrimônio (opcional) |

4. Clique em **Salvar**

### 8.3 Editar / Excluir uma garantia
- Mesmo procedimento dos itens 7.3 e 7.4 aplicado às garantias

> **Recomendação:** Inclua a localização física do equipamento no campo de observações (ex.: *"Rack sala de servidores — 2º andar"*) para facilitar chamados técnicos.

---

## 9. Acompanhamento de Vencimentos

### 9.1 Dashboard principal
- Ao fazer login, o **dashboard** exibe automaticamente:
  - Total de contratos ativos
  - Total de garantias ativas
  - Itens **a vencer nos próximos 60 dias** (alerta amarelo/laranja)
  - Itens **vencidos** (alerta vermelho)

### 9.2 Ação obrigatória ao receber alertas

| Situação | Ação recomendada |
|---|---|
| Contrato a vencer em 60 dias | Iniciar processo de renovação ou substituição de fornecedor |
| Garantia a vencer em 60 dias | Avaliar extensão de garantia ou contrato de manutenção |
| Contrato vencido | Regularizar imediatamente; atualizar status no sistema |
| Garantia vencida | Registrar decisão: contratar manutenção, desativar ou substituir equipamento |

### 9.3 Busca
- Use a barra de busca no topo para localizar registros por nome, fornecedor ou responsável
- Útil para verificar contratos/garantias de um fornecedor específico ou equipamento

---

## 10. Relatórios

1. Acesse **Relatórios** no menu lateral
2. Aplique os filtros desejados:
   - **Tipo**: Contratos ou Garantias
   - **Status**: Ativo / Vencido / A vencer
   - **Período**: intervalo de datas
   - **Categoria**: filtra por tipo de serviço ou equipamento
3. Clique em **Gerar Relatório**

> Relatórios são recomendados mensalmente e obrigatórios em auditorias ou inventários de TI.

---

## 11. Categorias Recomendadas para TI Hospitalar

Abaixo, sugestão de categorias a serem cadastradas no sistema pelo Gestor ou Admin:

**Contratos:**
- Manutenção de Hardware
- Suporte Técnico / SLA
- Licença de Software
- Conectividade / Internet
- Outsourcing de Impressão
- Locação de Equipamentos
- Segurança da Informação
- Backup e Armazenamento

**Garantias:**
- Servidor
- Computador / Notebook
- Impressora / Multifuncional
- Switch / Roteador / AP
- Nobreak / UPS
- Monitor
- Scanner / Leitor
- Equipamento Médico com TI
- Outro Periférico

> Para cadastrar categorias: menu lateral → **Categorias** → **Nova Categoria**

---

## 12. Controle de Acesso e Perfis

### 12.1 Estrutura de perfis

| Perfil | Permissões |
|---|---|
| **Admin** | Acesso total: usuários, setores, categorias, todos os registros |
| **Gestor** | Cria, edita e exclui contratos e garantias do setor; gerencia categorias próprias |
| **Visualizador** | Somente consulta — não altera dados |

### 12.2 Visibilidade por setor
- Colaboradores do **mesmo setor** visualizam os mesmos registros
- Usuário **sem setor** visualiza apenas seus próprios registros
- **Admin** visualiza todos os dados do sistema, independente de setor

---

## 13. Boas Práticas

1. **Registre imediatamente** — cadastre o contrato ou garantia no ato do recebimento da nota fiscal ou assinatura
2. **Número de série sempre** — preencha o número de série nas garantias; é essencial para acionar suporte técnico
3. **Status atualizado** — mantenha o status dos registros atualizado (Ativo/Inativo/Encerrado)
4. **Observações úteis** — use o campo de observações para registrar localização, patrimônio hospitalar e número de NF
5. **Nunca exclua histórico** — prefira mudar o status para *Encerrado* em vez de excluir; exclusões são irreversíveis
6. **Monitore o dashboard** — verifique os alertas de vencimento semanalmente
7. **Relatório mensal** — gere relatório de contratos e garantias a vencer no mês seguinte e encaminhe ao Coordenador de TI

---

## 14. Fluxo Resumido de Registro

```
Recebimento de NF / Assinatura de contrato
           ↓
Acessar ContraGuard → Login
           ↓
Contratos → Novo Contrato  OU  Garantias → Nova Garantia
           ↓
Preencher campos (nome, fornecedor, datas, categoria, observações)
           ↓
Salvar
           ↓
Verificar no dashboard se aparece corretamente
           ↓
Monitorar alertas de vencimento (60 dias antes)
           ↓
Tomar ação: renovar contrato / acionar garantia / substituir equipamento
```

---

## 15. Estrutura de Arquivos do Sistema

```
contraguard/
├── config/
│   └── config.php          # Configurações de banco e app
├── includes/
│   ├── db.php              # Conexão PDO
│   ├── functions.php       # isAdmin(), isGestor(), canEdit() etc.
│   ├── header.php          # Menu lateral + header
│   └── footer.php          # Rodapé e scripts
├── pages/
│   ├── contratos.php       # CRUD de contratos
│   ├── garantias.php       # CRUD de garantias
│   ├── relatorios.php      # Relatórios
│   ├── dashboard_analise.php
│   ├── usuarios.php        # Admin: gestão de usuários
│   ├── setores.php         # Admin: gestão de setores
│   ├── categorias.php      # Admin/Gestor: categorias
│   └── alterar_senha.php   # Troca de senha
├── assets/
│   ├── css/style.css
│   └── js/scripts.js
├── login.php
├── logout.php
└── index.php               # Dashboard principal
```

---

## 16. Requisitos Técnicos

| Componente | Versão recomendada |
|---|---|
| PHP | 7.4 ou superior |
| MySQL | 5.7 ou superior |
| Servidor web | Apache (XAMPP) |
| Bootstrap | 5.x |
| Font Awesome | 6.x |

---

## 17. Suporte e Manutenção

- Dúvidas ou problemas: contate o **Coordenador de TI** ou o administrador do sistema
- Backups do banco de dados: responsabilidade do administrador do servidor — realizar periodicamente
- Senhas armazenadas com **bcrypt** — nunca compartilhar credenciais
- Em caso de indisponibilidade do sistema, registre manualmente e insira os dados assim que o acesso for restabelecido

---

## 18. Histórico de Revisões

| Versão | Data | Alteração | Responsável |
|---|---|---|---|
| 1.0 | Abril/2026 | Criação do documento | Equipe de TI |
| 2.0 | Abril/2026 | Adaptação para setor de TI hospitalar | Equipe de TI |
