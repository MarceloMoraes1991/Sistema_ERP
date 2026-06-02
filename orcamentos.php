<?php
require_once("db/conexao.php");

$porPagina   = 15;
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$offset      = ($paginaAtual - 1) * $porPagina;
$busca       = trim($_GET['busca']   ?? '');
$filtEstado  = (int)($_GET['estado'] ?? 0);

$where = ['1=1'];
if ($busca !== '') {
    $b = mysqli_real_escape_string($con, $busca);
    $where[] = "(o.numero LIKE '%$b%' OR o.titulo LIKE '%$b%' OR o.cliente_nome LIKE '%$b%')";
}
if ($filtEstado > 0) $where[] = "o.estado_cod = $filtEstado";
$wh = implode(' AND ', $where);

$total    = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS t FROM orcamentos o WHERE $wh"))['t'] ?? 0);
$totalPag = (int)ceil($total / $porPagina);
$lista    = mysqli_query($con,
    "SELECT o.*, e.nome AS estado_nome, e.cor AS estado_cor
     FROM orcamentos o
     LEFT JOIN orcamento_estados e ON o.estado_cod = e.cod
     WHERE $wh ORDER BY o.criado_em DESC LIMIT $offset, $porPagina");

// Métricas
$met = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT
        COUNT(*) AS total,
        SUM(estado_cod=4) AS aprovados,
        SUM(estado_cod=2) AS enviados,
        SUM(estado_cod=5) AS recusados,
        SUM(CASE WHEN estado_cod=4 THEN total ELSE 0 END) AS valor_aprovado,
        SUM(total) AS valor_total
     FROM orcamentos")) ?? [];

$estados = mysqli_query($con, "SELECT * FROM orcamento_estados ORDER BY cod");

$msgs = [
    'criado'   => ['sucesso','Orçamento criado com sucesso!'],
    'editado'  => ['sucesso','Orçamento atualizado!'],
    'excluido' => ['aviso',  'Orçamento eliminado.'],
];
[$mt, $mm] = $msgs[$_GET['msg'] ?? ''] ?? ['',''];

require_once("header.php");

function badge_estado($cor, $nome) {
    $map = [
        'verde'    => 'badge-verde',
        'azul'     => 'badge-azul',
        'laranja'  => 'badge-laranja',
        'vermelho' => 'badge-vermelho',
        'amarelo'  => 'badge-amarelo',
        'cinza'    => 'badge-cinza',
    ];
    $cls = $map[$cor] ?? 'badge-cinza';
    return "<span class='badge $cls'>".htmlspecialchars($nome)."</span>";
}
?>

<main class="pagina">

  <div class="page-header">
    <div class="page-header-txt">
      <h1 class="pagina-titulo">Orçamentos</h1>
      <p class="pagina-subtitulo">Gestão de propostas e orçamentos comerciais</p>
    </div>
    <div class="page-header-acoes">
      <a href="orcamento_form.php" class="btn btn-primario">
        <i class="material-icons-round">add</i> Novo orçamento
      </a>
    </div>
  </div>

  <?php if ($mm): ?>
  <div class="alerta alerta-<?= $mt ?> mb-20">
    <i class="material-icons-round"><?= $mt==='sucesso'?'check_circle':'warning' ?></i>
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
      <div class="metrica-label"><i class="material-icons-round">request_quote</i> Total</div>
      <div class="metrica-valor"><?= number_format($met['total'] ?? 0) ?></div>
      <div class="metrica-sub">Orçamentos criados</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-ciano"></div>
      <div class="metrica-label"><i class="material-icons-round">send</i> Enviados</div>
      <div class="metrica-valor"><?= number_format($met['enviados'] ?? 0) ?></div>
      <div class="metrica-sub">Aguardar resposta</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-verde"></div>
      <div class="metrica-label"><i class="material-icons-round">check_circle</i> Aprovados</div>
      <div class="metrica-valor"><?= number_format($met['aprovados'] ?? 0) ?></div>
      <div class="metrica-sub">€ <?= number_format($met['valor_aprovado'] ?? 0, 2, ',', '.') ?></div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-vermelho"></div>
      <div class="metrica-label"><i class="material-icons-round">cancel</i> Recusados</div>
      <div class="metrica-valor"><?= number_format($met['recusados'] ?? 0) ?></div>
      <div class="metrica-sub">Propostas rejeitadas</div>
    </div>
  </div>

  <div class="card">

    <!-- Filtros -->
    <form method="GET" class="filtros-bar">
      <div class="form-grupo">
        <label class="form-label">Pesquisar</label>
        <div style="position:relative;">
          <i class="material-icons-round" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--c400);font-size:16px;pointer-events:none;">search</i>
          <input type="text" name="busca" class="form-input" style="padding-left:32px;width:260px;"
                 placeholder="Nº, título, cliente…" value="<?= htmlspecialchars($busca) ?>">
        </div>
      </div>
      <div class="form-grupo">
        <label class="form-label">Estado</label>
        <select name="estado" class="form-select" style="width:160px;">
          <option value="0">Todos</option>
          <?php mysqli_data_seek($estados,0); while ($est = mysqli_fetch_assoc($estados)): ?>
          <option value="<?= $est['cod'] ?>" <?= $filtEstado===(int)$est['cod']?'selected':'' ?>>
            <?= htmlspecialchars($est['nome']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-grupo" style="align-self:flex-end;">
        <button type="submit" class="btn btn-primario"><i class="material-icons-round">filter_list</i> Filtrar</button>
      </div>
      <?php if ($busca || $filtEstado): ?>
      <div class="form-grupo" style="align-self:flex-end;">
        <a href="orcamentos.php" class="btn btn-outline"><i class="material-icons-round">close</i> Limpar</a>
      </div>
      <?php endif; ?>
    </form>

    <div class="card-header" style="border-top:1px solid var(--c200);">
      <span class="card-titulo"><?= number_format($total) ?> orçamento(s)</span>
      <span style="font-size:12px;color:var(--c400);">
        Valor total: <strong style="color:var(--c800);">€ <?= number_format($met['valor_total'] ?? 0, 2, ',', '.') ?></strong>
      </span>
    </div>

    <div class="tabela-wrap">
      <table class="tabela">
        <thead>
          <tr>
            <th>Nº Orçamento</th>
            <th>Título</th>
            <th>Cliente</th>
            <th>Data emissão</th>
            <th>Validade</th>
            <th style="text-align:right;">Total (c/ IVA)</th>
            <th>Estado</th>
            <th style="text-align:center;">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($lista) === 0): ?>
          <tr><td colspan="8">
            <div class="vazio">
              <i class="material-icons-round">request_quote</i>
              <h3>Nenhum orçamento encontrado</h3>
              <p>Crie o primeiro orçamento para um cliente.</p>
              <a href="orcamento_form.php" class="btn btn-primario btn-sm">
                <i class="material-icons-round">add</i> Novo orçamento
              </a>
            </div>
          </td></tr>
        <?php else: ?>
          <?php while ($orc = mysqli_fetch_assoc($lista)):
            $vencido = $orc['validade'] && $orc['validade'] < date('Y-m-d') && $orc['estado_cod'] < 4;
          ?>
          <tr>
            <td>
              <div style="font-family:monospace;font-weight:600;color:var(--azul);font-size:13px;">
                <?= htmlspecialchars($orc['numero']) ?>
              </div>
            </td>
            <td>
              <div class="td-nome"><?= htmlspecialchars(mb_strimwidth($orc['titulo'],0,50,'…')) ?></div>
            </td>
            <td style="font-size:13px;color:var(--c600);"><?= htmlspecialchars($orc['cliente_nome']) ?></td>
            <td style="font-size:12px;color:var(--c500);"><?= date('d/m/Y', strtotime($orc['data_emissao'])) ?></td>
            <td style="font-size:12px;">
              <?php if ($orc['validade']): ?>
                <span style="color:<?= $vencido?'var(--vermelho)':'var(--c500)' ?>">
                  <?= date('d/m/Y', strtotime($orc['validade'])) ?>
                  <?= $vencido ? ' <span class="badge badge-vermelho" style="font-size:10px;">Vencido</span>' : '' ?>
                </span>
              <?php else: ?>
                <span style="color:var(--c300);">—</span>
              <?php endif; ?>
            </td>
            <td style="text-align:right;font-weight:600;color:var(--c900);">
              € <?= number_format($orc['total'], 2, ',', '.') ?>
            </td>
            <td><?= badge_estado($orc['estado_cor'], $orc['estado_nome']) ?></td>
            <td>
              <div class="acoes" style="justify-content:center;">
                <a href="orcamento_ver.php?cod=<?= $orc['cod'] ?>"
                   class="btn-icone" title="Ver orçamento" style="color:var(--azul);">
                  <i class="material-icons-round" style="font-size:14px;">visibility</i>
                </a>
                <a href="orcamento_ver.php?cod=<?= $orc['cod'] ?>&imprimir=1"
                   class="btn-icone" title="Imprimir / PDF" target="_blank">
                  <i class="material-icons-round" style="font-size:14px;">print</i>
                </a>
                <a href="orcamento_form.php?cod=<?= $orc['cod'] ?>" class="btn-icone" title="Editar">
                  <i class="material-icons-round" style="font-size:14px;">edit</i>
                </a>
                <a href="orcamento_duplicar.php?cod=<?= $orc['cod'] ?>"
                   class="btn-icone" title="Duplicar orçamento" style="color:var(--roxo);">
                  <i class="material-icons-round" style="font-size:14px;">content_copy</i>
                </a>
                <a href="orcamento_excluir.php?cod=<?= $orc['cod'] ?>"
                   class="btn-icone perigo" title="Eliminar"
                   onclick="return confirm('Eliminar o orçamento <?= addslashes(htmlspecialchars($orc['numero'])) ?>?')">
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
      $qs = http_build_query(array_filter(['busca'=>$busca,'estado'=>$filtEstado?:""]));
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
