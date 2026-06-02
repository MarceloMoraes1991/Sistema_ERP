<?php
require_once("bloqueio.php");

$cod    = $_SESSION['cod'];
$nome   = $_SESSION['nome'];
$email  = $_SESSION['email'];
$perfil = (int)$_SESSION['perfil'];

// Iniciais para avatar
$partes   = explode(' ', trim($nome));
$iniciais = strtoupper(substr($partes[0], 0, 1));
if (count($partes) > 1) $iniciais .= strtoupper(substr(end($partes), 0, 1));

$pg = basename($_SERVER['PHP_SELF']);

// Títulos de todas as páginas
$titulos = [
  // Principal
  'dashboard.php'              => 'Dashboard',
  'home.php'                   => 'Agenda / Tarefas',
  'cadastro_tarefa.php'        => 'Nova Tarefa',
  'editar_tarefa.php'          => 'Editar Tarefa',
  'excluir_tarefa.php'         => 'Excluir Tarefa',
  // Atividades
  'visualizar_atividade.php'   => 'Atividades',
  'editar_atividade.php'       => 'Editar Atividade',
  'atividades_arquivadas.php'  => 'Atividades Arquivadas',
  'arquivar_atividade.php'     => 'Arquivar Atividade',
  // Clientes
  'clientes.php'               => 'Clientes',
  'clientes_form.php'          => 'Ficha de Cliente',
  'clientes_detalhe.php'       => 'Detalhe do Cliente',
  'clientes_excluir.php'       => 'Eliminar Cliente',
  // Orçamentos
  'orcamentos.php'             => 'Orçamentos',
  'orcamento_form.php'         => 'Novo Orçamento',
  'orcamento_ver.php'          => 'Ver Orçamento',
  'orcamento_excluir.php'      => 'Eliminar Orçamento',
  'orcamento_duplicar.php'     => 'Duplicar Orçamento',
  'orcamento_estado.php'       => 'Estado do Orçamento',
  // Ordens de Serviço
  'os_form.php'                => 'Ordem de Serviço',
  'os_ver.php'                 => 'Ver OS',
  'os_imprimir.php'            => 'Imprimir OS',
  // Financeiro
  'financeiro_form.php'        => 'Lançamento Financeiro',
  'financeiro_pagar.php'       => 'Marcar como Pago',
  'financeiro_excluir.php'     => 'Eliminar Lançamento',
  // Funcionários
  'funcionarios.php'           => 'Funcionários',
  'funcionarios_form.php'      => 'Ficha de Funcionário',
  'funcionarios_detalhe.php'   => 'Detalhe do Funcionário',
  'funcionarios_excluir.php'   => 'Eliminar Funcionário',
  // Equipamentos
  'listar_equipamento.php'     => 'Equipamentos',
  'adicionar_equipamento.php'  => 'Novo Equipamento',
  'editar_equipamento.php'     => 'Editar Equipamento',
  'deletar_equipamento.php'    => 'Eliminar Equipamento',
  // Chips
  'chips.php'                  => 'Chips / Operadoras',
  'cadastro_chips.php'         => 'Novo Chip',
  'editar_chips.php'           => 'Editar Chip',
  'arquivar_chips.php'         => 'Arquivar Chip',
  // Stock
  'estoque.php'                => 'Stock',
  'estoque_form.php'           => 'Item de Stock',
  'estoque_movimentar.php'     => 'Movimentar Stock',
  'estoque_movimentacoes.php'  => 'Movimentações de Stock',
  'estoque_excluir.php'        => 'Eliminar Item',
  // Documentos
  'listar_contratos.php'       => 'Documentos / Contratos',
  'cad_contrato.php'           => 'Enviar Documento',
  'excluir_contrato.php'       => 'Eliminar Documento',
  // Administração
  'lista_usuarios.php'         => 'Utilizadores',
  'cadastro.php'               => 'Novo Utilizador',
  'editar_usuario.php'         => 'Editar Utilizador',
  'excluir_usuario.php'        => 'Eliminar Utilizador',
  'trocar_senha.php'           => 'Alterar Palavra-passe',
  'configuracoes.php'          => 'Configurações',
  // Backup
  'backup.php'                 => 'Cópia de Segurança',
  'backup_config_form.php'     => 'Configurar Backup',
  'backup_executar.php'        => 'Executar Backup',
  'ftp.php'                    => 'Gestor FTP',
];
$titulo_pg = $titulos[$pg] ?? 'ITM Technology';

// Helper para estado activo
function sbAtivo($pgs, $atual) {
    return in_array($atual, (array)$pgs) ? 'ativo' : '';
}

// ── Badges de alerta (opcional — protegido com @) ─────────
$os_abertas  = 0;
$fin_vencido = 0;
$stock_baixo = 0;

$r = @mysqli_query($con, "SELECT COUNT(*) AS t FROM ordens_servico WHERE status IN ('aberta','em_andamento','aguardando')");
if ($r) $os_abertas = (int)(mysqli_fetch_assoc($r)['t'] ?? 0);

$r = @mysqli_query($con, "SELECT COUNT(*) AS t FROM financeiro WHERE status='vencido' AND tipo='receita'");
if ($r) $fin_vencido = (int)(mysqli_fetch_assoc($r)['t'] ?? 0);

$r = @mysqli_query($con, "SELECT COUNT(*) AS t FROM estoque WHERE quantidade <= qtd_minima AND status='disponivel'");
if ($r) $stock_baixo = (int)(mysqli_fetch_assoc($r)['t'] ?? 0);

// Verifica se módulos existem
$tem_orcamentos = (bool)@mysqli_fetch_assoc(@mysqli_query($con, "SHOW TABLES LIKE 'orcamentos'"));
$tem_os         = (bool)@mysqli_fetch_assoc(@mysqli_query($con, "SHOW TABLES LIKE 'ordens_servico'"));
$tem_financeiro = (bool)@mysqli_fetch_assoc(@mysqli_query($con, "SHOW TABLES LIKE 'financeiro'"));
$tem_func       = (bool)@mysqli_fetch_assoc(@mysqli_query($con, "SHOW TABLES LIKE 'funcionarios'"));
$tem_backup     = (bool)@mysqli_fetch_assoc(@mysqli_query($con, "SHOW TABLES LIKE 'backup_configs'"));
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($titulo_pg) ?> — ITM Technology</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/sistema.css">
</head>
<body>

<div class="sb-overlay" id="sb-overlay"></div>
<div class="layout">

<!-- ══ SIDEBAR ═══════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">

  <!-- Logo -->
  <div class="sb-logo">
    <img src="assets/img/logo.svg" alt="ITM Technology"
         onerror="this.style.display='none';document.getElementById('logo-txt').style.display='flex'">
    <div id="logo-txt" style="display:none;align-items:center;gap:8px;">
      <div style="width:30px;height:30px;border-radius:6px;background:var(--azul);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="material-icons-round" style="font-size:17px;color:#fff;">router</i>
      </div>
      <div>
        <div class="sb-logo-texto">ITM Technology</div>
        <div class="sb-logo-sub">Sistema de Gestão</div>
      </div>
    </div>
  </div>

  <nav class="sb-nav">

    <!-- Principal -->
    <span class="sb-section">Principal</span>
    <a href="dashboard.php" class="sb-link <?= sbAtivo('dashboard.php', $pg) ?>">
      <i class="material-icons-round">dashboard</i> Dashboard
    </a>
    <a href="home.php" class="sb-link <?= sbAtivo(['home.php','cadastro_tarefa.php','editar_tarefa.php'], $pg) ?>">
      <i class="material-icons-round">calendar_month</i> Agenda / Tarefas
    </a>
    <a href="visualizar_atividade.php" class="sb-link <?= sbAtivo(['visualizar_atividade.php','editar_atividade.php','atividades_arquivadas.php'], $pg) ?>">
      <i class="material-icons-round">task_alt</i> Atividades
    </a>

    <!-- Comercial -->
    <span class="sb-section">Comercial</span>
    <a href="clientes.php" class="sb-link <?= sbAtivo(['clientes.php','clientes_form.php','clientes_detalhe.php'], $pg) ?>">
      <i class="material-icons-round">people</i> Clientes
    </a>

    <?php if ($tem_orcamentos): ?>
    <a href="orcamentos.php" class="sb-link <?= sbAtivo(['orcamentos.php','orcamento_form.php','orcamento_ver.php'], $pg) ?>">
      <i class="material-icons-round">request_quote</i> Orçamentos
    </a>
    <?php endif; ?>

    <?php if ($tem_os): ?>
    <a href="clientes.php" class="sb-link <?= sbAtivo(['os_form.php','os_ver.php'], $pg) ?>"
       title="Aceda às OS dentro da ficha do cliente">
      <i class="material-icons-round">build_circle</i> Ordens de Serviço
      <?php if ($os_abertas > 0): ?>
      <span class="badge-menu"><?= $os_abertas ?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>

    <?php if ($tem_financeiro): ?>
    <a href="clientes.php" class="sb-link <?= sbAtivo(['financeiro_form.php'], $pg) ?>"
       title="Aceda ao financeiro dentro da ficha do cliente">
      <i class="material-icons-round">account_balance_wallet</i> Financeiro
      <?php if ($fin_vencido > 0): ?>
      <span class="badge-menu"><?= $fin_vencido ?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>

    <!-- Recursos Humanos -->
    <?php if ($tem_func): ?>
    <span class="sb-section">Recursos Humanos</span>
    <a href="funcionarios.php" class="sb-link <?= sbAtivo(['funcionarios.php','funcionarios_form.php','funcionarios_detalhe.php'], $pg) ?>">
      <i class="material-icons-round">badge</i> Funcionários
    </a>
    <?php endif; ?>

    <!-- Infraestrutura -->
    <span class="sb-section">Infraestrutura</span>
    <a href="listar_equipamento.php" class="sb-link <?= sbAtivo(['listar_equipamento.php','adicionar_equipamento.php','editar_equipamento.php'], $pg) ?>">
      <i class="material-icons-round">computer</i> Equipamentos
    </a>
    <a href="chips.php" class="sb-link <?= sbAtivo(['chips.php','cadastro_chips.php','editar_chips.php'], $pg) ?>">
      <i class="material-icons-round">sim_card</i> Chips / Operadoras
    </a>
    <a href="estoque.php" class="sb-link <?= sbAtivo(['estoque.php','estoque_form.php','estoque_movimentar.php','estoque_movimentacoes.php'], $pg) ?>">
      <i class="material-icons-round">inventory_2</i> Stock
      <?php if ($stock_baixo > 0): ?>
      <span class="badge-menu"><?= $stock_baixo ?></span>
      <?php endif; ?>
    </a>
    <a href="listar_contratos.php" class="sb-link <?= sbAtivo(['listar_contratos.php','cad_contrato.php'], $pg) ?>">
      <i class="material-icons-round">folder_open</i> Documentos
    </a>

    <!-- Administração (só admins) -->
    <?php if ($perfil == 1): ?>
    <span class="sb-section">Administração</span>
    <a href="lista_usuarios.php" class="sb-link <?= sbAtivo(['lista_usuarios.php','cadastro.php','editar_usuario.php'], $pg) ?>">
      <i class="material-icons-round">manage_accounts</i> Utilizadores
    </a>
    <?php if ($tem_backup): ?>
    <a href="backup.php" class="sb-link <?= sbAtivo(['backup.php','backup_config_form.php','backup_executar.php'], $pg) ?>">
      <i class="material-icons-round">backup</i> Cópia de Segurança
    </a>
    <a href="ftp.php" class="sb-link <?= sbAtivo('ftp.php', $pg) ?>">
      <i class="material-icons-round">cloud_upload</i> Gestor FTP
    </a>
    <?php else: ?>
    <a href="backup.php" class="sb-link <?= sbAtivo('backup.php', $pg) ?>">
      <i class="material-icons-round">backup</i> Cópia de Segurança
    </a>
    <a href="ftp.php" class="sb-link <?= sbAtivo('ftp.php', $pg) ?>">
      <i class="material-icons-round">cloud_upload</i> FTP
    </a>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Conta -->
    <span class="sb-section">Conta</span>
    <a href="configuracoes.php" class="sb-link <?= sbAtivo('configuracoes.php', $pg) ?>">
      <i class="material-icons-round">settings</i> Configurações
    </a>
    <a href="trocar_senha.php" class="sb-link <?= sbAtivo('trocar_senha.php', $pg) ?>">
      <i class="material-icons-round">lock</i> Palavra-passe
    </a>

  </nav>

  <!-- Utilizador -->
  <div class="sb-usuario">
    <div class="sb-avatar"><?= $iniciais ?></div>
    <div style="flex:1;overflow:hidden;">
      <div class="sb-user-nome" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
        <?= htmlspecialchars($nome) ?>
      </div>
      <div class="sb-user-cargo"><?= $perfil==1?'Administrador':'Utilizador' ?></div>
    </div>
    <a href="db/sair.php" class="sb-sair" title="Terminar sessão">
      <i class="material-icons-round">logout</i>
    </a>
  </div>

</aside>
<!-- ══ /SIDEBAR ═══════════════════════════════════════════ -->

<!-- ══ CONTEÚDO ═══════════════════════════════════════════ -->
<div class="main-content">

  <!-- Topbar -->
  <header class="topbar">
    <div class="tb-esq">
      <button class="btn-menu" id="btn-menu" aria-label="Abrir menu">
        <i class="material-icons-round">menu</i>
      </button>
      <span class="tb-breadcrumb"><?= htmlspecialchars($titulo_pg) ?></span>
    </div>
    <div class="tb-dir">
      <form class="busca-form" method="GET" action="visualizar_atividade.php">
        <i class="material-icons-round busca-icon">search</i>
        <input type="text" name="search" class="busca-input"
               placeholder="Pesquisar atividades…"
               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
      </form>
      <?php if ($tem_orcamentos): ?>
      <a href="orcamento_form.php" class="btn btn-outline btn-sm">
        <i class="material-icons-round">request_quote</i> Orçamento
      </a>
      <?php endif; ?>
      <a href="cadastro_tarefa.php" class="btn btn-primario btn-sm">
        <i class="material-icons-round">add</i> Tarefa
      </a>
    </div>
  </header>
  <!-- /Topbar -->
