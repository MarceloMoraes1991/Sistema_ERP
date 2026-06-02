<?php
require_once("db/conexao.php");

$porPagina   = 12;
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$offset      = ($paginaAtual - 1) * $porPagina;
$search      = trim($_GET['search'] ?? '');
$filtStatus  = $_GET['status'] ?? '';

$where = ["status != 'arquivada'"];
if ($search !== '') {
    $s = mysqli_real_escape_string($con, $search);
    $where[] = "(titulo LIKE '%$s%' OR aberto_por LIKE '%$s%' OR observacao LIKE '%$s%' OR fomentar LIKE '%$s%')";
}
if ($filtStatus !== '') $where[] = "status = '".mysqli_real_escape_string($con,$filtStatus)."'";
$wh = implode(' AND ', $where);

$total    = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS t FROM atividades WHERE $wh"))['t'] ?? 0);
$totalPag = (int)ceil($total / $porPagina);
$result   = mysqli_query($con, "SELECT * FROM atividades WHERE $wh ORDER BY data_criacao DESC LIMIT $offset, $porPagina");

$tot = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT SUM(status='aberta') AS abertas,
            SUM(status='em_andamento') AS andamento,
            SUM(status='concluida') AS concluidas,
            COUNT(*) AS total
     FROM atividades WHERE status != 'arquivada'")) ?? [];

$msgs = [
    'criada'   => ['sucesso','Atividade criada com sucesso!'],
    'editada'  => ['sucesso','Atividade actualizada!'],
    'arquivada'=> ['aviso',  'Atividade arquivada.'],
    'erro'     => ['erro',   'Ocorreu um erro. Tente novamente.'],
];
[$mt, $mm] = $msgs[$_GET['msg'] ?? ''] ?? ['',''];

require_once("header.php");
?>

<main class="pagina">

  <div class="page-header">
    <div class="page-header-txt">
      <h1 class="pagina-titulo">Atividades</h1>
      <p class="pagina-subtitulo">Chamados e atividades de suporte</p>
    </div>
    <div class="page-header-acoes">
      <a href="atividades_arquivadas.php" class="btn btn-outline">
        <i class="material-icons-round">archive</i> Arquivadas
      </a>
      <a href="editar_atividade.php" class="btn btn-primario">
        <i class="material-icons-round">add</i> Nova atividade
      </a>
    </div>
  </div>

  <?php if ($mm): ?>
  <div class="alerta alerta-<?= $mt ?> mb-20">
    <i class="material-icons-round"><?= $mt==='sucesso'?'check_circle':($mt==='aviso'?'warning':'error') ?></i>
    <span><?= htmlspecialchars($mm) ?></span>
    <button data-fechar-alerta style="margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;">
      <i class="material-icons-round" style="font-size:18px;">close</i>
    </button>
  </div>
  <?php endif; ?>

  <!-- Métricas -->
  <div class="metricas mb-20">
    <div class="metrica">
      <div class="metrica-acento acento-azul"></div>
      <div class="metrica-label"><i class="material-icons-round">task_alt</i> Total activas</div>
      <div class="metrica-valor"><?= $tot['total'] ?? 0 ?></div>
      <div class="metrica-sub">Não arquivadas</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-laranja"></div>
      <div class="metrica-label"><i class="material-icons-round">pending</i> Em aberto</div>
      <div class="metrica-valor"><?= $tot['abertas'] ?? 0 ?></div>
      <div class="metrica-sub">Aguardam atendimento</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-roxo"></div>
      <div class="metrica-label"><i class="material-icons-round">autorenew</i> Em curso</div>
      <div class="metrica-valor"><?= $tot['andamento'] ?? 0 ?></div>
      <div class="metrica-sub">A ser tratadas</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-verde"></div>
      <div class="metrica-label"><i class="material-icons-round">check_circle</i> Concluídas</div>
      <div class="metrica-valor"><?= $tot['concluidas'] ?? 0 ?></div>
      <div class="metrica-sub">Finalizadas</div>
    </div>
  </div>

  <div class="card">

    <form method="GET" class="filtros-bar">
      <div class="form-grupo">
        <label class="form-label">Pesquisar</label>
        <div style="position:relative;">
          <i class="material-icons-round" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--c400);font-size:16px;pointer-events:none;">search</i>
          <input type="text" name="search" class="form-input" style="padding-left:32px;width:280px;"
                 placeholder="Título, responsável, descrição…" value="<?= htmlspecialchars($search) ?>">
        </div>
      </div>
      <div class="form-grupo">
        <label class="form-label">Estado</label>
        <select name="status" class="form-select" style="width:160px;">
          <option value="">Todos</option>
          <option value="aberta"       <?= $filtStatus==='aberta'      ?'selected':'' ?>>Em aberto</option>
          <option value="em_andamento" <?= $filtStatus==='em_andamento'?'selected':'' ?>>Em curso</option>
          <option value="concluida"    <?= $filtStatus==='concluida'   ?'selected':'' ?>>Concluída</option>
        </select>
      </div>
      <div class="form-grupo" style="align-self:flex-end;">
        <button type="submit" class="btn btn-primario"><i class="material-icons-round">filter_list</i> Filtrar</button>
      </div>
      <?php if ($search || $filtStatus): ?>
      <div class="form-grupo" style="align-self:flex-end;">
        <a href="visualizar_atividade.php" class="btn btn-outline"><i class="material-icons-round">close</i> Limpar</a>
      </div>
      <?php endif; ?>
    </form>

    <div class="card-header" style="border-top:1px solid var(--c200);">
      <span class="card-titulo"><?= number_format($total) ?> <?= $total===1?'atividade':'atividades' ?></span>
    </div>

    <div class="tabela-wrap">
      <table class="tabela">
        <thead>
          <tr>
            <th>#</th><th>Título</th><th>Aberto por</th><th>Responsável</th>
            <th>Estado</th><th>Data</th><th style="text-align:center;">Acções</th>
          </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($result) === 0): ?>
          <tr><td colspan="7">
            <div class="vazio">
              <i class="material-icons-round">task_alt</i>
              <h3>Nenhuma atividade encontrada</h3>
              <p>Ajuste os filtros ou crie uma nova atividade.</p>
              <a href="editar_atividade.php" class="btn btn-primario btn-sm">
                <i class="material-icons-round">add</i> Nova atividade
              </a>
            </div>
          </td></tr>
        <?php else: ?>
          <?php while ($a = mysqli_fetch_assoc($result)):
            $badge_map = [
              'aberta'       => ['badge-laranja','Em aberto'],
              'em_andamento' => ['badge-roxo',   'Em curso'],
              'concluida'    => ['badge-verde',   'Concluída'],
            ];
            [$badge, $label] = $badge_map[$a['status']] ?? ['badge-cinza', ucfirst($a['status'])];
          ?>
          <tr>
            <td style="color:var(--c400);font-size:12px;"><?= $a['cod'] ?></td>
            <td>
              <div class="td-nome"><?= htmlspecialchars(mb_strimwidth($a['titulo'],0,55,'…')) ?></div>
              <?php if ($a['observacao']): ?>
              <div class="td-sub"><?= htmlspecialchars(mb_strimwidth($a['observacao'],0,50,'…')) ?></div>
              <?php endif; ?>
            </td>
            <td style="font-size:13px;color:var(--c600);">
              <?= $a['aberto_por'] ? htmlspecialchars($a['aberto_por']) : '<span style="color:var(--c300);">—</span>' ?>
            </td>
            <td style="font-size:13px;color:var(--c600);">
              <?= $a['fomentar'] ? htmlspecialchars($a['fomentar']) : '<span style="color:var(--c300);">—</span>' ?>
            </td>
            <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
            <td style="font-size:12px;color:var(--c500);white-space:nowrap;">
              <?= date('d/m/Y', strtotime($a['data_criacao'])) ?>
              <div style="font-size:11px;"><?= date('H:i', strtotime($a['data_criacao'])) ?></div>
            </td>
            <td>
              <div class="acoes" style="justify-content:center;">
                <?php if ($a['status'] !== 'concluida'): ?>
                <a href="arquivar_atividade.php?cod=<?= $a['cod'] ?>&acao=concluir"
                   class="btn-icone" title="Marcar como concluída" style="color:var(--verde);"
                   onclick="return confirm('Marcar como concluída?')">
                  <i class="material-icons-round" style="font-size:14px;">check_circle</i>
                </a>
                <?php endif; ?>
                <a href="editar_atividade.php?cod=<?= $a['cod'] ?>" class="btn-icone" title="Editar">
                  <i class="material-icons-round" style="font-size:14px;">edit</i>
                </a>
                <a href="arquivar_atividade.php?cod=<?= $a['cod'] ?>&acao=arquivar"
                   class="btn-icone" title="Arquivar"
                   onclick="return confirm('Arquivar esta atividade?')">
                  <i class="material-icons-round" style="font-size:14px;">archive</i>
                </a>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPag > 1):
      $qs = http_build_query(array_filter(['search'=>$search,'status'=>$filtStatus]));
      $qs = $qs ? "&$qs" : '';
    ?>
    <div class="paginacao">
      <?php if ($paginaAtual > 1): ?><a href="?pagina=<?= $paginaAtual-1 ?><?= $qs ?>" class="pag-btn"><i class="material-icons-round">chevron_left</i></a><?php endif; ?>
      <?php for ($p=max(1,$paginaAtual-2); $p<=min($totalPag,$paginaAtual+2); $p++): ?>
        <a href="?pagina=<?= $p ?><?= $qs ?>" class="pag-btn <?= $p===$paginaAtual?'ativo':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <?php if ($paginaAtual < $totalPag): ?><a href="?pagina=<?= $paginaAtual+1 ?><?= $qs ?>" class="pag-btn"><i class="material-icons-round">chevron_right</i></a><?php endif; ?>
      <span class="pag-info"><?= number_format($total) ?> registos</span>
    </div>
    <?php endif; ?>
  </div>
</main>
<?php require_once("footer.php"); ?>
