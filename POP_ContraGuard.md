# POP — ContraGuard
**Procedimento Operacional Padrão**
Versão 1.0 | Abril de 2026

---

## 1. Identificação

| Campo | Informação |
|---|---|
| **Sistema** | ContraGuard |
| **Finalidade** | Gestão de contratos e garantias |
| **Tecnologia** | PHP + MySQL + Bootstrap 5 |
| **URL local** | http://localhost/contraguard |
| **Banco de dados** | dema5738_contraguard (host remoto) |
| **Fuso horário** | America/Sao_Paulo |

---

## 2. Estrutura de Acesso e Perfis

O sistema possui três perfis de usuário com permissões distintas:

### 2.1 Administrador (`admin`)
- Acesso total ao sistema
- Gerencia usuários, setores e categorias de todos os usuários
- Visualiza e edita todos os contratos e garantias
- Acessa relatórios, dashboards e análises globais

### 2.2 Gestor (`gestor`)
- Visualiza, cria, edita e exclui contratos e garantias do seu setor
- Gerencia suas próprias categorias
- Acessa relatórios e dashboards filtrados pelo setor
- **Não** gerencia usuários nem setores

### 2.3 Visualizador (`visualizador`)
- Somente leitura: visualiza contratos e garantias do seu setor
- Acessa relatórios filtrados pelo setor
- **Não** pode criar, editar ou excluir registros

---

## 3. Login e Sessão

### 3.1 Acesso ao sistema
1. Acesse `http://localhost/contraguard/login.php`
2. Informe **usuário** (login) e **senha**
3. Clique em **Entrar**

### 3.2 Encerramento de sessão
- Clique no **avatar** no canto superior direito
- Selecione **Sair**
- A sessão é destruída e o usuário é redirecionado para o login

### 3.3 Troca de senha
1. Clique no **avatar** → **Alterar Senha**
2. Informe a senha atual
3. Informe a nova senha (mínimo 6 caracteres)
4. Confirme a nova senha
5. Clique em **Salvar**

---

## 4. Contratos

### 4.1 Listagem
- Acesse **Contratos** no menu lateral
- A listagem exibe os contratos visíveis ao perfil logado (filtro por setor)
- Use o campo de **busca** no topo para filtrar por nome, fornecedor ou responsável
- Contratos com vencimento nos próximos 60 dias são destacados como alerta

### 4.2 Cadastrar contrato (admin/gestor)
1. Clique em **Novo Contrato**
2. Preencha os campos obrigatórios:
   - **Nome do contrato**
   - **Fornecedor**
   - **Responsável**
   - **Data de início** e **Data de vencimento**
   - **Valor** (opcional)
   - **Categoria** (selecionada entre as do setor)
   - **Status**: Ativo / Inativo / Encerrado
3. Clique em **Salvar**

### 4.3 Editar contrato (admin/gestor)
1. Na listagem, clique no ícone de **lápis** na linha do contrato
2. Altere os campos desejados
3. Clique em **Salvar**

### 4.4 Excluir contrato (admin/gestor)
1. Na listagem, clique no ícone de **lixeira** na linha do contrato
2. Confirme a exclusão na caixa de diálogo

> **Atenção:** A exclusão é permanente e não pode ser desfeita.

---

## 5. Garantias

### 5.1 Listagem
- Acesse **Garantias** no menu lateral
- A listagem exibe apenas as garantias visíveis ao perfil logado (filtro por setor)
- Garantias com vencimento nos próximos 60 dias são destacadas como alerta

### 5.2 Cadastrar garantia (admin/gestor)
1. Clique em **Nova Garantia**
2. Preencha:
   - **Nome**
   - **Fornecedor / Fabricante**
   - **Número de série** (opcional)
   - **Data de início** e **Data de vencimento**
   - **Categoria** (do setor)
   - **Status**
3. Clique em **Salvar**

### 5.3 Editar / Excluir garantia (admin/gestor)
- Mesmo procedimento descrito para contratos (itens 4.3 e 4.4)

---

## 6. Dashboard Principal

- Acesse **Principal** no menu lateral
- Exibe cards com totais: **Contratos ativos**, **Garantias ativas**, **A vencer (60 dias)**, **Vencidos**
- Os dados são filtrados pelo setor do usuário logado
- Admin visualiza todos os dados globalmente (sem filtro de setor)

### 6.1 Busca global
- Utilize a barra de busca no topo da página
- Filtra contratos e garantias por nome, fornecedor e responsável
- Um badge indica o termo buscado; clique em **Limpar** para remover o filtro

---

## 7. Dashboard de Análise

- Acesse **Análise** → subitem no menu lateral (expandível)
- Exibe gráficos e KPIs detalhados: distribuição por categoria, evolução mensal, status, valores
- Dados filtrados por setor (mesma lógica do dashboard principal)

---

## 8. Relatórios

- Acesse **Relatórios** no menu lateral (disponível para todos os perfis)
- Filtre por:
  - **Tipo**: Contratos / Garantias
  - **Status**
  - **Período**
  - **Categoria** (do setor do usuário)
- Clique em **Gerar Relatório** para visualizar os resultados
- Os resultados respeitam o filtro de setor do usuário logado

---

## 9. Categorias

- Acesse **Categorias** no menu lateral (admin e gestor)
- Cada categoria pertence a um usuário e é visível a todos do mesmo setor

### 9.1 Criar categoria
1. Clique em **Nova Categoria**
2. Informe o **nome**
3. Clique em **Salvar**

### 9.2 Editar / Excluir categoria
- **Admin**: pode editar ou excluir qualquer categoria
- **Gestor**: pode editar ou excluir apenas suas próprias categorias

---

## 10. Setores (somente admin)

- Acesse **Setores** no menu lateral (admin)
- Permite criar, editar e excluir setores
- Usuários vinculados ao mesmo setor compartilham visibilidade de dados

---

## 11. Usuários (somente admin)

- Acesse **Usuários** no menu lateral (admin)
- Permite criar, editar e desativar usuários

### 11.1 Campos do usuário
| Campo | Descrição |
|---|---|
| **Nome** | Nome completo |
| **Login** | Nome de usuário para acesso |
| **Senha** | Mínimo 6 caracteres (armazenada com hash bcrypt) |
| **Perfil** | admin / gestor / visualizador |
| **Setor** | Setor ao qual o usuário pertence |

---

## 12. Regras de Visibilidade de Dados

| Perfil | Dados visíveis |
|---|---|
| **admin** | Todos os contratos, garantias e categorias do sistema |
| **gestor** | Contratos, garantias e categorias de todos os usuários do seu setor |
| **visualizador** | Contratos, garantias e categorias de todos os usuários do seu setor |

> Usuários **sem setor** vinculado visualizam apenas seus próprios registros.

---

## 13. Alertas de Vencimento

- O sistema recalcula alertas automaticamente a cada acesso ao dashboard
- Registros com vencimento nos próximos **60 dias** são marcados como "A vencer"
- Registros com **data passada** são marcados como "Vencido"
- Os alertas aparecem nos cards do dashboard e são destacados nas listagens

---

## 14. Estrutura de Arquivos

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

## 15. Requisitos Técnicos

| Componente | Versão recomendada |
|---|---|
| PHP | 7.4 ou superior |
| MySQL | 5.7 ou superior |
| Servidor web | Apache (XAMPP) |
| Bootstrap | 5.x |
| Font Awesome | 6.x |

---

## 16. Suporte e Manutenção

- Backups do banco de dados devem ser realizados periodicamente pelo administrador do servidor
- Senhas são armazenadas com **bcrypt** (`password_hash` / `password_verify`) — nunca em texto puro
- Em caso de acesso ao banco via CLI, utilizar credenciais do arquivo `config/config.php`
