<?php
require_once("db/conexao.php");
require_once("header.php");

// ── Função segura — não quebra se a tabela não existir ────
function qs($con, $sql, $campo = 't') {
    $r = @mysqli_query($con, $sql);
    if (!$r) return 0;
    $row = mysqli_fetch_assoc($r);
    return $row[$campo] ?? 0;
}
function qr($con, $sql) {
    $r = @mysqli_query($con, $sql);
    return $r ?: false;
}

// ── Métricas principais ────────────────────────────────────
$total_clientes     = qs($con, "SELECT COUNT(*) AS t FROM clientes WHERE status='ativo'");
$total_atividades   = qs($con, "SELECT COUNT(*) AS t FROM atividades WHERE status NOT IN ('arquivada')");
$ativ_abertas       = qs($con, "SELECT COUNT(*) AS t FROM atividades WHERE status='aberta'");
$total_equipamentos = qs($con, "SELECT COUNT(*) AS t FROM controle_equipamentos");
$stock_baixo        = qs($con, "SELECT COUNT(*) AS t FROM estoque WHERE quantidade <= qtd_minima AND status='disponivel'");
$total_funcionarios = qs($con, "SELECT COUNT(*) AS t FROM funcionarios WHERE status='ativo'");

// ── Orçamentos (opcional — só se tabela existir) ───────────
$tem_orcamentos = @mysqli_query($con, "SHOW TABLES LIKE 'orcamentos'");
$tem_orcamentos = $tem_orcamentos && mysqli_num_rows($tem_orcamentos) > 0;

$orc_mes_total = 0;
$orc_mes_val   = 0;
$orc_aprov_total = 0;
$orc_aprov_val   = 0;

if ($tem_orcamentos) {
    $r = @mysqli_fetch_assoc(mysqli_query($con,
        "SELECT COUNT(*) AS t, COALESCE(SUM(total),0) AS val
         FROM orcamentos WHERE MONTH(criado_em)=MONTH(NOW()) AND YEAR(criado_em)=YEAR(NOW())"));
    $orc_mes_total = $r['t']   ?? 0;
    $orc_mes_val   = $r['val'] ?? 0;

    $r2 = @mysqli_fetch_assoc(mysqli_query($con,
        "SELECT COUNT(*) AS t, COALESCE(SUM(total),0) AS val FROM orcamentos WHERE estado_cod=4"));
    $orc_aprov_total = $r2['t']   ?? 0;
    $orc_aprov_val   = $r2['val'] ?? 0;
}

// ── OS abertas (opcional) ──────────────────────────────────
$tem_os = @mysqli_query($con, "SHOW TABLES LIKE 'ordens_servico'");
$tem_os = $tem_os && mysqli_num_rows($tem_os) > 0;
$os_abertas = $tem_os ? qs($con, "SELECT COUNT(*) AS t FROM ordens_servico WHERE status IN ('aberta','em_andamento','aguardando')") : 0;

// ── Financeiro vencido (opcional) ─────────────────────────
$tem_fin = @mysqli_query($con, "SHOW TABLES LIKE 'financeiro'");
$tem_fin = $tem_fin && mysqli_num_rows($tem_fin) > 0;
$fin_vencido_val = $tem_fin ? qs($con, "SELECT COALESCE(SUM(valor),0) AS t FROM financeiro WHERE status='vencido' AND tipo='receita'", 't') : 0;

// ── Dados das listas ───────────────────────────────────────
$atividades = qr($con,
    "SELECT * FROM atividades WHERE status != 'arquivada' ORDER BY data_criacao DESC LIMIT 5");

$orcamentos_rec = ($tem_orcamentos)
    ? qr($con, "SELECT o.*, e.nome AS estado_nome, e.cor AS estado_cor
                FROM orcamentos o
                LEFT JOIN orcamento_estados e ON o.estado_cod = e.cod
                ORDER BY o.criado_em DESC LIMIT 5")
    : false;

$hoje = date('Y-m-d');
$perfil_s = (int)$_SESSION['perfil'];
$uc_s     = (int)$_SESSION['cod'];
$sql_t = $perfil_s == 1
    ? "SELECT t.*, c.nome AS cat FROM tarefas t JOIN categoria_tarefa c ON t.categoria_cod=c.cod WHERE t.data='$hoje' ORDER BY t.hora LIMIT 6"
    : "SELECT t.*, c.nome AS cat FROM tarefas t JOIN categoria_tarefa c ON t.categoria_cod=c.cod WHERE t.data='$hoje' AND t.usuario_cod=$uc_s ORDER BY t.hora LIMIT 6";
$tarefas_hoje = qr($con, $sql_t);

$primeiro_nome = explode(' ', trim($_SESSION['nome']))[0];
?>

<main class="pagina">

  <div class="page-header">
    <div class="page-header-txt">
      <h1 class="pagina-titulo">Dashboard</h1>
      <p class="pagina-subtitulo">
        Olá, <strong><?= htmlspecialchars($primeiro_nome) ?></strong>!
        <?= date('d \d\e F \d\e Y') ?>
      </p>
    </div>
    <div class="page-header-acoes">
      <?php if ($tem_orcamentos): ?>
      <a href="orcamento_form.php" class="btn btn-outline btn-sm">
        <i class="material-icons-round">request_quote</i> Orçamento
      </a>
      <?php endif; ?>
      <a href="cadastro_tarefa.php" class="btn btn-primario btn-sm">
        <i class="material-icons-round">add</i> Nova tarefa
      </a>
    </div>
  </div>

  <!-- Alertas -->
  <?php if ($stock_baixo > 0): ?>
  <div class="alerta alerta-aviso mb-20">
    <i class="material-icons-round">warning</i>
    <span><strong><?= $stock_baixo ?> item(ns) de stock</strong> abaixo do mínimo.
    <a href="estoque.php" style="color:inherit;text-decoration:underline;font-weight:600;">Ver stock →</a></span>
  </div>
  <?php endif; ?>

  <?php if ($os_abertas > 0): ?>
  <div class="alerta alerta-info mb-20">
    <i class="material-icons-round">build_circle</i>
    <span><strong><?= $os_abertas ?> Ordem(ns) de Serviço</strong> em aberto.
    <a href="clientes.php" style="color:inherit;text-decoration:underline;font-weight:600;">Ver clientes →</a></span>
  </div>
  <?php endif; ?>

  <?php if ($fin_vencido_val > 0): ?>
  <div class="alerta alerta-erro mb-20">
    <i class="material-icons-round">warning</i>
    <span><strong>€ <?= number_format($fin_vencido_val,2,',','.') ?></strong> em pagamentos vencidos.
    <a href="clientes.php" style="color:inherit;text-decoration:underline;font-weight:600;">Ver financeiro →</a></span>
  </div>
  <?php endif; ?>

  <!-- Métricas -->
  <div class="metricas mb-20">
    <div class="metrica">
      <div class="metrica-acento acento-azul"></div>
      <div class="metrica-label"><i class="material-icons-round">people</i> Clientes</div>
      <div class="metrica-valor"><?= number_format($total_clientes) ?></div>
      <div class="metrica-sub">Clientes activos</div>
    </div>
    <?php if ($tem_orcamentos): ?>
    <div class="metrica">
      <div class="metrica-acento acento-ciano"></div>
      <div class="metrica-label"><i class="material-icons-round">request_quote</i> Orçamentos (mês)</div>
      <div class="metrica-valor"><?= number_format($orc_mes_total) ?></div>
      <div class="metrica-sub">€ <?= number_format($orc_mes_val,2,',','.') ?></div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-verde"></div>
      <div class="metrica-label"><i class="material-icons-round">check_circle</i> Aprovados</div>
      <div class="metrica-valor"><?= number_format($orc_aprov_total) ?></div>
      <div class="metrica-sub">€ <?= number_format($orc_aprov_val,2,',','.') ?></div>
    </div>
    <?php endif; ?>
    <div class="metrica">
      <div class="metrica-acento acento-laranja"></div>
      <div class="metrica-label"><i class="material-icons-round">task_alt</i> Atividades</div>
      <div class="metrica-valor"><?= number_format($total_atividades) ?></div>
      <div class="metrica-sub"><?= $ativ_abertas ?> em aberto</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-roxo"></div>
      <div class="metrica-label"><i class="material-icons-round">badge</i> Funcionários</div>
      <div class="metrica-valor"><?= number_format($total_funcionarios) ?></div>
      <div class="metrica-sub">Activos</div>
    </div>
  </div>

  <!-- Grade principal -->
  <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:14px;margin-bottom:14px;">

    <!-- Atividades recentes -->
    <div class="card">
      <div class="card-header">
        <span class="card-titulo">Atividades recentes</span>
        <a href="visualizar_atividade.php" class="btn btn-outline btn-sm">Ver todas</a>
      </div>
      <?php if ($atividades && mysqli_num_rows($atividades) > 0): ?>
      <div class="tabela-wrap">
        <table class="tabela">
          <thead><tr><th>Título</th><th>Estado</th><th>Data</th><th></th></tr></thead>
          <tbody>
          <?php while ($a = mysqli_fetch_assoc($atividades)):
            $bs = match($a['status']) {
              'aberta'       => 'badge-laranja',
              'em_andamento' => 'badge-roxo',
              'concluida'    => 'badge-verde',
              default        => 'badge-cinza',
            };
            $ls = match($a['status']) {
              'aberta'       => 'Aberta',
              'em_andamento' => 'Em curso',
              'concluida'    => 'Concluída',
              default        => ucfirst($a['status']),
            };
          ?>
          <tr>
            <td><div class="td-nome"><?= htmlspecialchars(mb_strimwidth($a['titulo'],0,42,'…')) ?></div></td>
            <td><span class="badge <?= $bs ?>"><?= $ls ?></span></td>
            <td style="font-size:12px;color:var(--c500);"><?= date('d/m/Y',strtotime($a['data_criacao'])) ?></td>
            <td>
              <a href="editar_atividade.php?cod=<?= $a['cod'] ?>" class="btn-icone">
                <i class="material-icons-round" style="font-size:14px;">edit</i>
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="vazio" style="padding:28px;">
        <i class="material-icons-round">task_alt</i>
        <h3>Sem atividades</h3>
        <a href="editar_atividade.php" class="btn btn-primario btn-sm"><i class="material-icons-round">add</i> Criar</a>
      </div>
      <?php endif; ?>
    </div>

    <!-- Tarefas de hoje -->
    <div class="card">
      <div class="card-header">
        <span class="card-titulo">
          <i class="material-icons-round" style="font-size:16px;vertical-align:-3px;color:var(--azul);">today</i>
          Hoje — <?= date('d/m') ?>
        </span>
        <a href="home.php" class="btn btn-outline btn-sm">Agenda</a>
      </div>
      <?php if ($tarefas_hoje && mysqli_num_rows($tarefas_hoje) > 0): ?>
      <div>
        <?php while ($t = mysqli_fetch_assoc($tarefas_hoje)): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--c100);">
          <div style="font-size:12px;font-weight:700;color:var(--azul);min-width:38px;flex-shrink:0;">
            <?= date('H:i',strtotime($t['hora'])) ?>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:500;color:var(--c900);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($t['titulo']) ?></div>
            <div style="font-size:11px;color:var(--c500);"><?= htmlspecialchars($t['cat']) ?></div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
      <?php else: ?>
      <div style="text-align:center;padding:28px;color:var(--c400);">
        <i class="material-icons-round" style="font-size:32px;display:block;margin-bottom:8px;">event_available</i>
        <div style="font-size:13px;">Sem tarefas para hoje</div>
        <a href="cadastro_tarefa.php" class="btn btn-primario btn-sm" style="margin-top:12px;">
          <i class="material-icons-round">add</i> Adicionar
        </a>
      </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Grade secundária -->
  <div class="grid-2">

    <!-- Orçamentos recentes (só se existir) -->
    <?php if ($tem_orcamentos && $orcamentos_rec && mysqli_num_rows($orcamentos_rec) > 0): ?>
    <div class="card">
      <div class="card-header">
        <span class="card-titulo">Orçamentos recentes</span>
        <a href="orcamentos.php" class="btn btn-outline btn-sm">Ver todos</a>
      </div>
      <div class="tabela-wrap">
        <table class="tabela">
          <thead><tr><th>Nº</th><th>Cliente</th><th style="text-align:right;">Total</th><th>Estado</th></tr></thead>
          <tbody>
          <?php
          $bmap=['cinza'=>'badge-cinza','azul'=>'badge-azul','laranja'=>'badge-laranja','verde'=>'badge-verde','vermelho'=>'badge-vermelho','amarelo'=>'badge-amarelo'];
          while ($orc = mysqli_fetch_assoc($orcamentos_rec)):
            $b = $bmap[$orc['estado_cor']] ?? 'badge-cinza';
          ?>
          <tr>
            <td><a href="orcamento_ver.php?cod=<?= $orc['cod'] ?>" style="color:var(--azul);font-family:monospace;font-size:12px;font-weight:600;"><?= htmlspecialchars($orc['numero']) ?></a></td>
            <td style="font-size:13px;color:var(--c600);"><?= htmlspecialchars(mb_strimwidth($orc['cliente_nome'],0,22,'…')) ?></td>
            <td style="text-align:right;font-weight:600;">€ <?= number_format($orc['total'],2,',','.') ?></td>
            <td><span class="badge <?= $b ?>" style="font-size:10px;"><?= htmlspecialchars($orc['estado_nome']) ?></span></td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Atalhos rápidos -->
    <div class="card">
      <div class="card-header"><span class="card-titulo">Atalhos rápidos</span></div>
      <div class="card-body">
        <div class="atalhos-grid">
          <a href="clientes.php"              class="atalho"><i class="material-icons-round">people</i> Clientes</a>
          <?php if ($tem_orcamentos): ?>
          <a href="orcamento_form.php"        class="atalho"><i class="material-icons-round">request_quote</i> Orçamento</a>
          <?php endif; ?>
          <a href="visualizar_atividade.php"  class="atalho"><i class="material-icons-round">task_alt</i> Atividades</a>
          <a href="listar_equipamento.php"    class="atalho"><i class="material-icons-round">computer</i> Equipamentos</a>
          <a href="estoque.php"               class="atalho"><i class="material-icons-round">inventory_2</i> Stock</a>
          <a href="funcionarios.php"          class="atalho"><i class="material-icons-round">badge</i> Funcionários</a>
          <a href="chips.php"                 class="atalho"><i class="material-icons-round">sim_card</i> Chips</a>
          <a href="listar_contratos.php"      class="atalho"><i class="material-icons-round">folder_open</i> Documentos</a>
          <?php if ($perfil_s == 1): ?>
          <a href="backup.php"               class="atalho"><i class="material-icons-round">backup</i> Backup</a>
          <a href="lista_usuarios.php"       class="atalho"><i class="material-icons-round">manage_accounts</i> Utilizadores</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>

</main>

<?php require_once("footer.php"); ?>
