<?php
require_once("db/conexao.php");

$porPagina   = 20;
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$offset      = ($paginaAtual - 1) * $porPagina;
$busca       = trim($_GET['busca']    ?? '');
$filtTipo    = $_GET['tipo_mov']      ?? '';
$filtItem    = (int)($_GET['item']    ?? 0);
$dataInicio  = $_GET['data_inicio']   ?? '';
$dataFim     = $_GET['data_fim']      ?? '';

$where = ['1=1'];
if ($busca !== '') {
    $b = mysqli_real_escape_string($con,$busca);
    $where[] = "(e.nome LIKE '%$b%' OR m.motivo LIKE '%$b%' OR m.responsavel LIKE '%$b%')";
}
if ($filtTipo !== '') $where[] = "m.tipo_mov='".mysqli_real_escape_string($con,$filtTipo)."'";
if ($filtItem  > 0)  $where[] = "m.estoque_cod=$filtItem";
if ($dataInicio!=='') $where[] = "DATE(m.data_mov)>='".mysqli_real_escape_string($con,$dataInicio)."'";
if ($dataFim   !=='') $where[] = "DATE(m.data_mov)<='".mysqli_real_escape_string($con,$dataFim)."'";
$wh = implode(' AND ',$where);

$total    = (int)(mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) AS t FROM estoque_movimentacoes m LEFT JOIN estoque e ON m.estoque_cod=e.cod WHERE $wh"))['t']??0);
$totalPag = (int)ceil($total/$porPagina);

$movs = mysqli_query($con,
    "SELECT m.*, e.nome AS item_nome, e.tipo AS item_tipo, u.nome AS user_nome
     FROM estoque_movimentacoes m
     LEFT JOIN estoque e ON m.estoque_cod=e.cod
     LEFT JOIN usuario u ON m.usuario_cod=u.cod
     WHERE $wh ORDER BY m.data_mov DESC LIMIT $offset,$porPagina");

$tot = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT SUM(tipo_mov='entrada') AS entradas, SUM(tipo_mov='saida') AS saidas, SUM(tipo_mov='ajuste') AS ajustes,
            SUM(CASE WHEN tipo_mov='entrada' THEN quantidade ELSE 0 END) AS qtd_ent,
            SUM(CASE WHEN tipo_mov='saida'   THEN quantidade ELSE 0 END) AS qtd_sai
     FROM estoque_movimentacoes m LEFT JOIN estoque e ON m.estoque_cod=e.cod WHERE $wh"))??[];

$itens_lista = mysqli_query($con,"SELECT cod,nome FROM estoque ORDER BY nome");

require_once("header.php");
?>

<main class="pagina">
  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="estoque.php" class="btn-icone"><i class="material-icons-round">arrow_back</i></a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo">Histórico de Movimentações</h1>
        <p class="pagina-subtitulo">Todas as entradas, saídas e ajustes de stock</p>
      </div>
    </div>
  </div>

  <div class="metricas mb-20">
    <div class="metrica"><div class="metrica-acento acento-azul"></div><div class="metrica-label"><i class="material-icons-round">receipt_long</i> Total</div><div class="metrica-valor"><?= number_format($total) ?></div><div class="metrica-sub">Movimentações</div></div>
    <div class="metrica"><div class="metrica-acento acento-verde"></div><div class="metrica-label"><i class="material-icons-round">add_circle</i> Entradas</div><div class="metrica-valor"><?= number_format($tot['entradas']??0) ?></div><div class="metrica-sub"><?= number_format($tot['qtd_ent']??0) ?> unidades</div></div>
    <div class="metrica"><div class="metrica-acento acento-vermelho"></div><div class="metrica-label"><i class="material-icons-round">remove_circle</i> Saídas</div><div class="metrica-valor"><?= number_format($tot['saidas']??0) ?></div><div class="metrica-sub"><?= number_format($tot['qtd_sai']??0) ?> unidades</div></div>
    <div class="metrica"><div class="metrica-acento acento-laranja"></div><div class="metrica-label"><i class="material-icons-round">tune</i> Ajustes</div><div class="metrica-valor"><?= number_format($tot['ajustes']??0) ?></div><div class="metrica-sub">Correcções manuais</div></div>
  </div>

  <div class="card">
    <form method="GET" class="filtros-bar">
      <div class="form-grupo">
        <label class="form-label">Pesquisar</label>
        <div style="position:relative;">
          <i class="material-icons-round" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--c400);font-size:16px;pointer-events:none;">search</i>
          <input type="text" name="busca" class="form-input" style="padding-left:32px;width:200px;"
                 placeholder="Item, motivo, responsável…" value="<?= htmlspecialchars($busca) ?>">
        </div>
      </div>
      <div class="form-grupo">
        <label class="form-label">Tipo</label>
        <select name="tipo_mov" class="form-select" style="width:130px;">
          <option value="">Todos</option>
          <option value="entrada" <?= $filtTipo==='entrada'?'selected':'' ?>>Entrada</option>
          <option value="saida"   <?= $filtTipo==='saida'  ?'selected':'' ?>>Saída</option>
          <option value="ajuste"  <?= $filtTipo==='ajuste' ?'selected':'' ?>>Ajuste</option>
        </select>
      </div>
      <div class="form-grupo">
        <label class="form-label">Item</label>
        <select name="item" class="form-select" style="width:160px;">
          <option value="0">Todos</option>
          <?php while ($it=mysqli_fetch_assoc($itens_lista)): ?>
          <option value="<?= $it['cod'] ?>" <?= $filtItem===(int)$it['cod']?'selected':'' ?>>
            <?= htmlspecialchars(mb_strimwidth($it['nome'],0,28,'…')) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-grupo">
        <label class="form-label">De</label>
        <input type="date" name="data_inicio" class="form-input" style="width:135px;" value="<?= htmlspecialchars($dataInicio) ?>">
      </div>
      <div class="form-grupo">
        <label class="form-label">Até</label>
        <input type="date" name="data_fim" class="form-input" style="width:135px;" value="<?= htmlspecialchars($dataFim) ?>">
      </div>
      <div class="form-grupo" style="align-self:flex-end;">
        <button type="submit" class="btn btn-primario"><i class="material-icons-round">filter_list</i> Filtrar</button>
      </div>
      <?php if ($busca||$filtTipo||$filtItem||$dataInicio||$dataFim): ?>
      <div class="form-grupo" style="align-self:flex-end;">
        <a href="estoque_movimentacoes.php" class="btn btn-outline"><i class="material-icons-round">close</i> Limpar</a>
      </div>
      <?php endif; ?>
    </form>

    <div class="tabela-wrap">
      <table class="tabela">
        <thead><tr><th>Data</th><th>Item</th><th>Tipo</th><th style="text-align:center;">Quantidade</th><th>Motivo</th><th>Responsável</th><th>Utilizador</th></tr></thead>
        <tbody>
        <?php if (mysqli_num_rows($movs)===0): ?>
          <tr><td colspan="7"><div class="vazio"><i class="material-icons-round">swap_horiz</i><h3>Nenhuma movimentação encontrada</h3></div></td></tr>
        <?php else: ?>
          <?php while ($m=mysqli_fetch_assoc($movs)):
            [$badge,$label,$icon,$sinal] = match($m['tipo_mov']) {
              'entrada'=>['badge-verde','Entrada','add_circle','+'],
              'saida'  =>['badge-vermelho','Saída','remove_circle','-'],
              'ajuste' =>['badge-azul','Ajuste','tune','='],
              default  =>['badge-cinza',$m['tipo_mov'],'swap_horiz',''],
            };
            $cor_qtd = match($m['tipo_mov']){'entrada'=>'var(--verde)','saida'=>'var(--vermelho)',default=>'var(--azul)'};
          ?>
          <tr>
            <td style="white-space:nowrap;">
              <div style="font-size:13px;font-weight:500;color:var(--c800);"><?= date('d/m/Y',strtotime($m['data_mov'])) ?></div>
              <div style="font-size:11px;color:var(--c500);"><?= date('H:i',strtotime($m['data_mov'])) ?></div>
            </td>
            <td>
              <?php if ($m['item_nome']): ?>
              <div class="td-nome"><?= htmlspecialchars(mb_strimwidth($m['item_nome'],0,40,'…')) ?></div>
              <div class="td-sub"><?= $m['item_tipo']==='material'?'Material':'Equipamento' ?></div>
              <?php else: ?><span style="color:var(--c300);">Item removido</span><?php endif; ?>
            </td>
            <td><span class="badge <?= $badge ?>" style="gap:4px;"><i class="material-icons-round" style="font-size:12px;"><?= $icon ?></i><?= $label ?></span></td>
            <td style="text-align:center;"><span style="font-size:16px;font-weight:700;color:<?= $cor_qtd ?>;"><?= $sinal ?><?= number_format($m['quantidade']) ?></span></td>
            <td style="font-size:13px;color:var(--c600);"><?= $m['motivo']?htmlspecialchars(mb_strimwidth($m['motivo'],0,45,'…')):'<span style="color:var(--c300);">—</span>' ?></td>
            <td style="font-size:13px;color:var(--c600);"><?= $m['responsavel']?htmlspecialchars($m['responsavel']):'<span style="color:var(--c300);">—</span>' ?></td>
            <td>
              <?php if ($m['user_nome']): $p=explode(' ',trim($m['user_nome'])); $av=strtoupper(substr($p[0],0,1)).(count($p)>1?strtoupper(substr(end($p),0,1)):''); ?>
              <div style="display:flex;align-items:center;gap:6px;">
                <div class="avatar-mini"><?= $av ?></div>
                <span style="font-size:12px;color:var(--c600);"><?= htmlspecialchars(explode(' ',$m['user_nome'])[0]) ?></span>
              </div>
              <?php else: ?><span style="color:var(--c300);">—</span><?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPag>1):
      $qs=http_build_query(array_filter(['busca'=>$busca,'tipo_mov'=>$filtTipo,'item'=>$filtItem?:'','data_inicio'=>$dataInicio,'data_fim'=>$dataFim]));
      $qs=$qs?"&$qs":'';
    ?>
    <div class="paginacao">
      <?php if ($paginaAtual>1): ?><a href="?pagina=<?= $paginaAtual-1 ?><?= $qs ?>" class="pag-btn"><i class="material-icons-round">chevron_left</i></a><?php endif; ?>
      <?php for ($p=max(1,$paginaAtual-2);$p<=min($totalPag,$paginaAtual+2);$p++): ?>
        <a href="?pagina=<?= $p ?><?= $qs ?>" class="pag-btn <?= $p===$paginaAtual?'ativo':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <?php if ($paginaAtual<$totalPag): ?><a href="?pagina=<?= $paginaAtual+1 ?><?= $qs ?>" class="pag-btn"><i class="material-icons-round">chevron_right</i></a><?php endif; ?>
      <span class="pag-info"><?= number_format($total) ?> registos · Pág. <?= $paginaAtual ?>/<?= $totalPag ?></span>
    </div>
    <?php endif; ?>
  </div>
</main>
<?php require_once("footer.php"); ?>
