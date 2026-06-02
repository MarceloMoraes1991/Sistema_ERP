<?php
require_once("db/conexao.php");

$porPagina   = 20;
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$offset      = ($paginaAtual - 1) * $porPagina;
$search      = trim($_GET['search'] ?? '');
$filtStatus  = $_GET['status'] ?? 'ativo';

$where = ['1=1'];
if ($search !== '') {
    $s = mysqli_real_escape_string($con, $search);
    $where[] = "(nome LIKE '%$s%' OR numero LIKE '%$s%' OR operadora LIKE '%$s%')";
}
if ($filtStatus !== '') $where[] = "status = '".mysqli_real_escape_string($con,$filtStatus)."'";
$wh = implode(' AND ', $where);

$total    = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS t FROM chips WHERE $wh"))['t'] ?? 0);
$totalPag = (int)ceil($total / $porPagina);
$result   = mysqli_query($con, "SELECT * FROM chips WHERE $wh ORDER BY criado_em DESC LIMIT $offset, $porPagina");

$tot = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT SUM(status='ativo') AS ativos, SUM(status='arquivado') AS arquivados, COUNT(*) AS total FROM chips")) ?? [];

$msgs = [
    'criado'   => ['sucesso','Chip registado com sucesso!'],
    'editado'  => ['sucesso','Chip actualizado!'],
    'arquivado'=> ['aviso',  'Chip arquivado.'],
];
[$mt, $mm] = $msgs[$_GET['msg'] ?? ''] ?? ['',''];

require_once("header.php");
?>

<main class="pagina">
  <div class="page-header">
    <div class="page-header-txt">
      <h1 class="pagina-titulo">Chips / Operadoras</h1>
      <p class="pagina-subtitulo">Gestão de cartões SIM e números de telefone</p>
    </div>
    <div class="page-header-acoes">
      <?php if ($filtStatus === 'ativo'): ?>
      <a href="chips.php?status=arquivado" class="btn btn-outline">
        <i class="material-icons-round">archive</i> Arquivados
      </a>
      <?php else: ?>
      <a href="chips.php" class="btn btn-outline">
        <i class="material-icons-round">sim_card</i> Activos
      </a>
      <?php endif; ?>
      <a href="cadastro_chips.php" class="btn btn-primario">
        <i class="material-icons-round">add</i> Novo chip
      </a>
    </div>
  </div>

  <?php if ($mm): ?>
  <div class="alerta alerta-<?= $mt ?> mb-20">
    <i class="material-icons-round"><?= $mt==='sucesso'?'check_circle':'warning' ?></i>
    <span><?= htmlspecialchars($mm) ?></span>
    <button data-fechar-alerta style="margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;"><i class="material-icons-round" style="font-size:18px;">close</i></button>
  </div>
  <?php endif; ?>

  <div class="metricas mb-20">
    <div class="metrica">
      <div class="metrica-acento acento-azul"></div>
      <div class="metrica-label"><i class="material-icons-round">sim_card</i> Total</div>
      <div class="metrica-valor"><?= $tot['total'] ?? 0 ?></div>
      <div class="metrica-sub">Chips registados</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-verde"></div>
      <div class="metrica-label"><i class="material-icons-round">check_circle</i> Activos</div>
      <div class="metrica-valor"><?= $tot['ativos'] ?? 0 ?></div>
      <div class="metrica-sub">Em utilização</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-laranja"></div>
      <div class="metrica-label"><i class="material-icons-round">archive</i> Arquivados</div>
      <div class="metrica-valor"><?= $tot['arquivados'] ?? 0 ?></div>
      <div class="metrica-sub">Desactivados</div>
    </div>
  </div>

  <div class="card">
    <form method="GET" class="filtros-bar">
      <div class="form-grupo">
        <label class="form-label">Pesquisar</label>
        <div style="position:relative;">
          <i class="material-icons-round" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--c400);font-size:16px;pointer-events:none;">search</i>
          <input type="text" name="search" class="form-input" style="padding-left:32px;width:260px;"
                 placeholder="Nome, número, operadora…" value="<?= htmlspecialchars($search) ?>">
        </div>
      </div>
      <input type="hidden" name="status" value="<?= htmlspecialchars($filtStatus) ?>">
      <div class="form-grupo" style="align-self:flex-end;">
        <button type="submit" class="btn btn-primario"><i class="material-icons-round">search</i> Pesquisar</button>
      </div>
      <?php if ($search): ?>
      <div class="form-grupo" style="align-self:flex-end;">
        <a href="chips.php?status=<?= $filtStatus ?>" class="btn btn-outline"><i class="material-icons-round">close</i> Limpar</a>
      </div>
      <?php endif; ?>
    </form>

    <div class="card-header" style="border-top:1px solid var(--c200);">
      <span class="card-titulo"><?= number_format($total) ?> <?= $total===1?'chip':'chips' ?>
        <?= $filtStatus==='arquivado'?' arquivado(s)':' activo(s)' ?>
      </span>
    </div>

    <div class="tabela-wrap">
      <table class="tabela">
        <thead>
          <tr>
            <th>Nome / Identificação</th><th>Número</th>
            <th>Operadora</th><th>Estado</th>
            <th style="text-align:center;">Acções</th>
          </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($result) === 0): ?>
          <tr><td colspan="5">
            <div class="vazio">
              <i class="material-icons-round">sim_card</i>
              <h3>Nenhum chip encontrado</h3>
              <a href="cadastro_chips.php" class="btn btn-primario btn-sm"><i class="material-icons-round">add</i> Registar chip</a>
            </div>
          </td></tr>
        <?php else: ?>
          <?php while ($chip = mysqli_fetch_assoc($result)): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:28px;height:28px;border-radius:var(--r-sm);background:var(--roxo-claro);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <i class="material-icons-round" style="font-size:15px;color:var(--roxo);">sim_card</i>
                </div>
                <span class="td-nome"><?= htmlspecialchars($chip['nome']) ?></span>
              </div>
            </td>
            <td>
              <?php if ($chip['numero']): ?>
              <a href="https://wa.me/351<?= preg_replace('/\D/','',$chip['numero']) ?>"
                 target="_blank"
                 style="display:inline-flex;align-items:center;gap:5px;color:var(--verde);font-size:13px;font-weight:500;">
                <i class="material-icons-round" style="font-size:14px;">chat</i>
                <?= htmlspecialchars($chip['numero']) ?>
              </a>
              <?php else: ?>
              <span style="color:var(--c300);">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($chip['operadora']): ?>
              <span class="badge badge-azul"><?= htmlspecialchars($chip['operadora']) ?></span>
              <?php else: ?>
              <span style="color:var(--c300);">—</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= $chip['status']==='ativo'?'badge-verde':'badge-cinza' ?>">
                <?= $chip['status']==='ativo'?'Activo':'Arquivado' ?>
              </span>
            </td>
            <td>
              <div class="acoes" style="justify-content:center;">
                <a href="cadastro_chips.php?cod=<?= $chip['cod'] ?>" class="btn-icone" title="Editar">
                  <i class="material-icons-round" style="font-size:14px;">edit</i>
                </a>
                <?php if ($chip['status']==='ativo'): ?>
                <a href="arquivar_chips.php?cod=<?= $chip['cod'] ?>"
                   class="btn-icone" title="Arquivar"
                   onclick="return confirm('Arquivar este chip?')">
                  <i class="material-icons-round" style="font-size:14px;">archive</i>
                </a>
                <?php else: ?>
                <a href="arquivar_chips.php?cod=<?= $chip['cod'] ?>&acao=reativar"
                   class="btn-icone" title="Reactivar" style="color:var(--verde);">
                  <i class="material-icons-round" style="font-size:14px;">unarchive</i>
                </a>
                <?php endif; ?>
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
