<?php
require_once("db/conexao.php");

$porPagina   = 15;
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$offset      = ($paginaAtual - 1) * $porPagina;
$search      = trim($_GET['search'] ?? '');

$where = ["status='arquivada'"];
if ($search !== '') {
    $s = mysqli_real_escape_string($con, $search);
    $where[] = "(titulo LIKE '%$s%' OR aberto_por LIKE '%$s%')";
}
$wh = implode(' AND ', $where);

$total    = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS t FROM atividades WHERE $wh"))['t'] ?? 0);
$totalPag = (int)ceil($total / $porPagina);
$result   = mysqli_query($con, "SELECT * FROM atividades WHERE $wh ORDER BY data_fechamento DESC LIMIT $offset, $porPagina");

require_once("header.php");
?>
<main class="pagina">
  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="visualizar_atividade.php" class="btn-icone"><i class="material-icons-round">arrow_back</i></a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo">Atividades Arquivadas</h1>
        <p class="pagina-subtitulo"><?= number_format($total) ?> atividade(s) arquivada(s)</p>
      </div>
    </div>
  </div>

  <div class="card">
    <form method="GET" class="filtros-bar">
      <div class="form-grupo">
        <label class="form-label">Pesquisar</label>
        <div style="position:relative;">
          <i class="material-icons-round" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--c400);font-size:16px;pointer-events:none;">search</i>
          <input type="text" name="search" class="form-input" style="padding-left:32px;width:280px;"
                 placeholder="Título ou responsável…" value="<?= htmlspecialchars($search) ?>">
        </div>
      </div>
      <div class="form-grupo" style="align-self:flex-end;">
        <button type="submit" class="btn btn-primario"><i class="material-icons-round">search</i> Pesquisar</button>
      </div>
    </form>

    <div class="tabela-wrap">
      <table class="tabela">
        <thead>
          <tr><th>#</th><th>Título</th><th>Aberto por</th><th>Arquivada em</th><th style="text-align:center;">Acções</th></tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($result) === 0): ?>
          <tr><td colspan="5">
            <div class="vazio">
              <i class="material-icons-round">archive</i>
              <h3>Nenhuma atividade arquivada</h3>
            </div>
          </td></tr>
        <?php else: ?>
          <?php while ($a = mysqli_fetch_assoc($result)): ?>
          <tr>
            <td style="color:var(--c400);font-size:12px;"><?= $a['cod'] ?></td>
            <td>
              <div class="td-nome"><?= htmlspecialchars(mb_strimwidth($a['titulo'],0,55,'…')) ?></div>
              <?php if ($a['observacao']): ?>
              <div class="td-sub"><?= htmlspecialchars(mb_strimwidth($a['observacao'],0,50,'…')) ?></div>
              <?php endif; ?>
            </td>
            <td style="font-size:13px;color:var(--c600);"><?= htmlspecialchars($a['aberto_por'] ?: '—') ?></td>
            <td style="font-size:12px;color:var(--c500);">
              <?= $a['data_fechamento'] ? date('d/m/Y H:i', strtotime($a['data_fechamento'])) : '—' ?>
            </td>
            <td>
              <div class="acoes" style="justify-content:center;">
                <a href="editar_atividade.php?cod=<?= $a['cod'] ?>" class="btn-icone" title="Editar">
                  <i class="material-icons-round" style="font-size:14px;">edit</i>
                </a>
                <a href="arquivar_atividade.php?cod=<?= $a['cod'] ?>&acao=reabrir"
                   class="btn-icone" title="Reabrir" style="color:var(--azul);">
                  <i class="material-icons-round" style="font-size:14px;">unarchive</i>
                </a>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPag > 1): $qs = $search ? '&search='.urlencode($search) : ''; ?>
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
