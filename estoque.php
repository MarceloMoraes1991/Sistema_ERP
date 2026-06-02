<?php
require_once("db/conexao.php");

$porPagina   = 15;
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$offset      = ($paginaAtual - 1) * $porPagina;
$busca       = trim($_GET['busca']      ?? '');
$filtTipo    = $_GET['tipo']            ?? '';
$filtStatus  = $_GET['status']         ?? '';
$filtCat     = (int)($_GET['categoria']?? 0);

$where = ['1=1'];
if ($busca !== '') {
    $b = mysqli_real_escape_string($con, $busca);
    $where[] = "(e.nome LIKE '%$b%' OR e.marca LIKE '%$b%' OR e.modelo LIKE '%$b%' OR e.numero_serie LIKE '%$b%' OR e.patrimonio LIKE '%$b%' OR e.localizacao LIKE '%$b%')";
}
if ($filtTipo   !== '') $where[] = "e.tipo = '".mysqli_real_escape_string($con,$filtTipo)."'";
if ($filtStatus !== '') $where[] = "e.status = '".mysqli_real_escape_string($con,$filtStatus)."'";
if ($filtCat     > 0)   $where[] = "e.categoria_cod = $filtCat";
$wh = implode(' AND ', $where);

$total    = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS t FROM estoque e WHERE $wh"))['t'] ?? 0);
$totalPag = (int)ceil($total / $porPagina);
$itens    = mysqli_query($con,
    "SELECT e.*, c.nome AS cat_nome
     FROM estoque e
     JOIN estoque_categorias c ON e.categoria_cod = c.cod
     WHERE $wh ORDER BY e.atualizado_em DESC LIMIT $offset, $porPagina");

$met = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) AS tipos, SUM(quantidade) AS total_unidades,
            SUM(tipo='material') AS materiais,
            SUM(tipo='equipamento') AS equipamentos,
            SUM(quantidade <= qtd_minima AND status='disponivel') AS baixo
     FROM estoque")) ?? [];

$categorias = mysqli_query($con, "SELECT * FROM estoque_categorias ORDER BY nome");

$msgs = [
    'criado'   => ['sucesso','Item adicionado ao stock!'],
    'editado'  => ['sucesso','Item actualizado com sucesso!'],
    'excluido' => ['aviso',  'Item removido do stock.'],
    'movido'   => ['sucesso','Movimentação registada com sucesso!'],
];
[$mt, $mm] = $msgs[$_GET['msg'] ?? ''] ?? ['',''];

require_once("header.php");
?>

<main class="pagina">

  <div class="page-header">
    <div class="page-header-txt">
      <h1 class="pagina-titulo">Stock</h1>
      <p class="pagina-subtitulo">Controlo de materiais e equipamentos em armazém</p>
    </div>
    <div class="page-header-acoes">
      <a href="estoque_movimentacoes.php" class="btn btn-outline">
        <i class="material-icons-round">swap_horiz</i> Movimentações
      </a>
      <a href="estoque_form.php" class="btn btn-primario">
        <i class="material-icons-round">add</i> Novo item
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

  <?php if (($met['baixo'] ?? 0) > 0): ?>
  <div class="alerta alerta-aviso mb-20">
    <i class="material-icons-round">warning</i>
    <span><strong><?= $met['baixo'] ?> item(ns)</strong> com quantidade abaixo do mínimo.
    <a href="?status=disponivel" style="color:inherit;text-decoration:underline;font-weight:600;">Ver itens →</a></span>
  </div>
  <?php endif; ?>

  <!-- Métricas -->
  <div class="metricas mb-20">
    <div class="metrica">
      <div class="metrica-acento acento-azul"></div>
      <div class="metrica-label"><i class="material-icons-round">inventory_2</i> Tipos de item</div>
      <div class="metrica-valor"><?= number_format($met['tipos'] ?? 0) ?></div>
      <div class="metrica-sub">Itens registados</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-verde"></div>
      <div class="metrica-label"><i class="material-icons-round">numbers</i> Unidades</div>
      <div class="metrica-valor"><?= number_format($met['total_unidades'] ?? 0) ?></div>
      <div class="metrica-sub">Total em stock</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-laranja"></div>
      <div class="metrica-label"><i class="material-icons-round">build</i> Materiais</div>
      <div class="metrica-valor"><?= $met['materiais'] ?? 0 ?></div>
      <div class="metrica-sub">Tipos de peça</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-roxo"></div>
      <div class="metrica-label"><i class="material-icons-round">computer</i> Equipamentos</div>
      <div class="metrica-valor"><?= $met['equipamentos'] ?? 0 ?></div>
      <div class="metrica-sub">Tipos registados</div>
    </div>
  </div>

  <div class="card">

    <form method="GET" class="filtros-bar">
      <div class="form-grupo">
        <label class="form-label">Pesquisar</label>
        <div style="position:relative;">
          <i class="material-icons-round" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--c400);font-size:16px;pointer-events:none;">search</i>
          <input type="text" name="busca" class="form-input" style="padding-left:32px;width:230px;"
                 placeholder="Nome, marca, modelo, S/N…" value="<?= htmlspecialchars($busca) ?>">
        </div>
      </div>
      <div class="form-grupo">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select" style="width:150px;">
          <option value="">Todos</option>
          <option value="material"    <?= $filtTipo==='material'   ?'selected':'' ?>>Material</option>
          <option value="equipamento" <?= $filtTipo==='equipamento'?'selected':'' ?>>Equipamento</option>
        </select>
      </div>
      <div class="form-grupo">
        <label class="form-label">Estado</label>
        <select name="status" class="form-select" style="width:150px;">
          <option value="">Todos</option>
          <option value="disponivel" <?= $filtStatus==='disponivel'?'selected':'' ?>>Disponível</option>
          <option value="em_uso"     <?= $filtStatus==='em_uso'    ?'selected':'' ?>>Em uso</option>
          <option value="manutencao" <?= $filtStatus==='manutencao'?'selected':'' ?>>Manutenção</option>
          <option value="baixado"    <?= $filtStatus==='baixado'   ?'selected':'' ?>>Abatido</option>
        </select>
      </div>
      <div class="form-grupo">
        <label class="form-label">Categoria</label>
        <select name="categoria" class="form-select" style="width:170px;">
          <option value="0">Todas</option>
          <?php mysqli_data_seek($categorias,0); while ($cat = mysqli_fetch_assoc($categorias)): ?>
          <option value="<?= $cat['cod'] ?>" <?= $filtCat===(int)$cat['cod']?'selected':'' ?>>
            <?= htmlspecialchars($cat['nome']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-grupo" style="align-self:flex-end;">
        <button type="submit" class="btn btn-primario"><i class="material-icons-round">filter_list</i> Filtrar</button>
      </div>
      <?php if ($busca || $filtTipo || $filtStatus || $filtCat): ?>
      <div class="form-grupo" style="align-self:flex-end;">
        <a href="estoque.php" class="btn btn-outline"><i class="material-icons-round">close</i> Limpar</a>
      </div>
      <?php endif; ?>
    </form>

    <div class="card-header" style="border-top:1px solid var(--c200);">
      <span class="card-titulo"><?= number_format($total) ?> <?= $total===1?'item':'itens' ?></span>
      <span style="font-size:12px;color:var(--c400);">Pág. <?= $paginaAtual ?>/<?= max(1,$totalPag) ?></span>
    </div>

    <div class="tabela-wrap">
      <table class="tabela">
        <thead>
          <tr>
            <th>Item</th><th>Tipo</th><th>Categoria</th>
            <th>Marca / Modelo</th><th>Localização</th>
            <th style="text-align:center;">Quantidade</th>
            <th>Estado</th><th style="text-align:center;">Acções</th>
          </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($itens) === 0): ?>
          <tr><td colspan="8">
            <div class="vazio">
              <i class="material-icons-round">inventory_2</i>
              <h3>Nenhum item encontrado</h3>
              <p>Ajuste os filtros ou adicione um novo item.</p>
              <a href="estoque_form.php" class="btn btn-primario btn-sm">
                <i class="material-icons-round">add</i> Adicionar item
              </a>
            </div>
          </td></tr>
        <?php else: ?>
          <?php while ($item = mysqli_fetch_assoc($itens)):
            $baixo = ($item['quantidade'] <= $item['qtd_minima']) && $item['status']==='disponivel';
            $badge_status = match($item['status']) {
              'disponivel' => 'badge-verde',
              'em_uso'     => 'badge-azul',
              'manutencao' => 'badge-laranja',
              'baixado'    => 'badge-cinza',
              default      => 'badge-cinza',
            };
            $label_status = match($item['status']) {
              'disponivel' => 'Disponível',
              'em_uso'     => 'Em uso',
              'manutencao' => 'Manutenção',
              'baixado'    => 'Abatido',
              default      => $item['status'],
            };
            $ic_bg  = $item['tipo']==='material' ? 'var(--azul-claro)' : 'var(--roxo-claro)';
            $ic_cor = $item['tipo']==='material' ? 'var(--azul)'       : 'var(--roxo)';
            $ic     = $item['tipo']==='material' ? 'build'             : 'computer';
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:30px;height:30px;border-radius:var(--r-sm);background:<?= $ic_bg ?>;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <i class="material-icons-round" style="font-size:15px;color:<?= $ic_cor ?>;"><?= $ic ?></i>
                </div>
                <div>
                  <div class="td-nome"><?= htmlspecialchars($item['nome']) ?></div>
                  <?php if ($item['numero_serie']): ?>
                  <div class="td-sub" style="font-family:monospace;">S/N: <?= htmlspecialchars($item['numero_serie']) ?></div>
                  <?php elseif ($item['patrimonio']): ?>
                  <div class="td-sub">Pat: <?= htmlspecialchars($item['patrimonio']) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td>
              <span class="badge <?= $item['tipo']==='material'?'badge-azul':'badge-roxo' ?>">
                <?= $item['tipo']==='material'?'Material':'Equipamento' ?>
              </span>
            </td>
            <td style="font-size:12.5px;color:var(--c600);"><?= htmlspecialchars($item['cat_nome']) ?></td>
            <td style="font-size:13px;color:var(--c600);">
              <?= htmlspecialchars(implode(' · ', array_filter([$item['marca'],$item['modelo']]))) ?: '—' ?>
            </td>
            <td style="font-size:12.5px;color:var(--c600);"><?= htmlspecialchars($item['localizacao'] ?: '—') ?></td>
            <td style="text-align:center;">
              <div style="display:inline-flex;align-items:center;gap:4px;">
                <span style="font-size:16px;font-weight:700;color:<?= $baixo?'var(--vermelho)':'var(--c900)' ?>;">
                  <?= $item['quantidade'] ?>
                </span>
                <?php if ($baixo): ?>
                <i class="material-icons-round" style="font-size:14px;color:var(--vermelho);"
                   title="Abaixo do mínimo (<?= $item['qtd_minima'] ?>)">warning</i>
                <?php endif; ?>
              </div>
              <div style="font-size:10px;color:var(--c400);">mín: <?= $item['qtd_minima'] ?></div>
            </td>
            <td><span class="badge <?= $badge_status ?>"><?= $label_status ?></span></td>
            <td>
              <div class="acoes" style="justify-content:center;">
                <a href="estoque_movimentar.php?cod=<?= $item['cod'] ?>"
                   class="btn-icone" title="Movimentar" style="color:var(--verde);">
                  <i class="material-icons-round" style="font-size:14px;">swap_horiz</i>
                </a>
                <a href="estoque_form.php?cod=<?= $item['cod'] ?>" class="btn-icone" title="Editar">
                  <i class="material-icons-round" style="font-size:14px;">edit</i>
                </a>
                <a href="estoque_excluir.php?cod=<?= $item['cod'] ?>"
                   class="btn-icone perigo" title="Eliminar"
                   onclick="return confirm('Eliminar «<?= addslashes(htmlspecialchars($item['nome'])) ?>»?')">
                  <i class="material-icons-round" style="font-size:14px;">delete</i>
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
      $qs = http_build_query(array_filter(['busca'=>$busca,'tipo'=>$filtTipo,'status'=>$filtStatus,'categoria'=>$filtCat?:'']));
      $qs = $qs ? "&$qs" : '';
    ?>
    <div class="paginacao">
      <?php if ($paginaAtual > 1): ?><a href="?pagina=<?= $paginaAtual-1 ?><?= $qs ?>" class="pag-btn"><i class="material-icons-round">chevron_left</i></a><?php endif; ?>
      <?php for ($p=max(1,$paginaAtual-2); $p<=min($totalPag,$paginaAtual+2); $p++): ?>
        <a href="?pagina=<?= $p ?><?= $qs ?>" class="pag-btn <?= $p===$paginaAtual?'ativo':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <?php if ($paginaAtual < $totalPag): ?><a href="?pagina=<?= $paginaAtual+1 ?><?= $qs ?>" class="pag-btn"><i class="material-icons-round">chevron_right</i></a><?php endif; ?>
      <span class="pag-info"><?= number_format($total) ?> itens · Pág. <?= $paginaAtual ?>/<?= $totalPag ?></span>
    </div>
    <?php endif; ?>
  </div>
</main>
<?php require_once("footer.php"); ?>
