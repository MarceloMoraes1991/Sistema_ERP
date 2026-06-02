<?php
require_once("db/conexao.php");

$porPagina   = 15;
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$offset      = ($paginaAtual - 1) * $porPagina;
$search      = trim($_GET['search'] ?? '');

$where = ['1=1'];
if ($search !== '') {
    $s = mysqli_real_escape_string($con, $search);
    $where[] = "(nome_funcionario LIKE '%$s%' OR cpf LIKE '%$s%' OR equipamento LIKE '%$s%' OR modelo LIKE '%$s%' OR sn LIKE '%$s%' OR fabricante LIKE '%$s%')";
}
$wh = implode(' AND ', $where);

$total    = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS t FROM controle_equipamentos WHERE $wh"))['t'] ?? 0);
$totalPag = (int)ceil($total / $porPagina);
$result   = mysqli_query($con, "SELECT * FROM controle_equipamentos WHERE $wh ORDER BY data_cadastro DESC LIMIT $offset, $porPagina");

$msgs = [
    'criado'   => ['sucesso','Equipamento registado com sucesso!'],
    'editado'  => ['sucesso','Equipamento actualizado!'],
    'excluido' => ['aviso',  'Equipamento removido.'],
];
[$mt, $mm] = $msgs[$_GET['msg'] ?? ''] ?? ['',''];

require_once("header.php");
?>

<main class="pagina">
  <div class="page-header">
    <div class="page-header-txt">
      <h1 class="pagina-titulo">Equipamentos</h1>
      <p class="pagina-subtitulo">Controlo de equipamentos por colaborador</p>
    </div>
    <div class="page-header-acoes">
      <a href="adicionar_equipamento.php" class="btn btn-primario">
        <i class="material-icons-round">add</i> Novo equipamento
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

  <div class="card">
    <form method="GET" class="filtros-bar">
      <div class="form-grupo">
        <label class="form-label">Pesquisar</label>
        <div style="position:relative;">
          <i class="material-icons-round" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--c400);font-size:16px;pointer-events:none;">search</i>
          <input type="text" name="search" class="form-input" style="padding-left:32px;width:300px;"
                 placeholder="Colaborador, NIF, equipamento, modelo, S/N…"
                 value="<?= htmlspecialchars($search) ?>">
        </div>
      </div>
      <div class="form-grupo" style="align-self:flex-end;">
        <button type="submit" class="btn btn-primario"><i class="material-icons-round">search</i> Pesquisar</button>
      </div>
      <?php if ($search): ?>
      <div class="form-grupo" style="align-self:flex-end;">
        <a href="listar_equipamento.php" class="btn btn-outline"><i class="material-icons-round">close</i> Limpar</a>
      </div>
      <?php endif; ?>
    </form>

    <div class="card-header" style="border-top:1px solid var(--c200);">
      <span class="card-titulo"><?= number_format($total) ?> <?= $total===1?'equipamento':'equipamentos' ?></span>
    </div>

    <div class="tabela-wrap">
      <table class="tabela">
        <thead>
          <tr>
            <th>Colaborador</th><th>NIF</th><th>Equipamento</th>
            <th>Modelo</th><th>Fabricante</th><th>Nº Série</th>
            <th>Registado em</th><th style="text-align:center;">Acções</th>
          </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($result) === 0): ?>
          <tr><td colspan="8">
            <div class="vazio">
              <i class="material-icons-round">computer</i>
              <h3>Nenhum equipamento encontrado</h3>
              <a href="adicionar_equipamento.php" class="btn btn-primario btn-sm"><i class="material-icons-round">add</i> Registar</a>
            </div>
          </td></tr>
        <?php else: ?>
          <?php while ($e = mysqli_fetch_assoc($result)): ?>
          <tr>
            <td><div class="td-nome"><?= htmlspecialchars($e['nome_funcionario'] ?: '—') ?></div></td>
            <td style="font-size:12.5px;color:var(--c600);font-family:monospace;"><?= htmlspecialchars($e['cpf'] ?: '—') ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:28px;height:28px;border-radius:var(--r-sm);background:var(--azul-claro);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <i class="material-icons-round" style="font-size:15px;color:var(--azul);">computer</i>
                </div>
                <span style="font-weight:500;color:var(--c800);"><?= htmlspecialchars($e['equipamento']) ?></span>
              </div>
            </td>
            <td style="color:var(--c600);"><?= htmlspecialchars($e['modelo'] ?: '—') ?></td>
            <td style="color:var(--c600);"><?= htmlspecialchars($e['fabricante'] ?: '—') ?></td>
            <td style="font-size:12.5px;font-family:monospace;color:var(--c600);"><?= htmlspecialchars($e['sn'] ?: '—') ?></td>
            <td style="font-size:12px;color:var(--c500);"><?= date('d/m/Y', strtotime($e['data_cadastro'])) ?></td>
            <td>
              <div class="acoes" style="justify-content:center;">
                <a href="adicionar_equipamento.php?cod=<?= $e['cod'] ?>" class="btn-icone" title="Editar">
                  <i class="material-icons-round" style="font-size:14px;">edit</i>
                </a>
                <a href="deletar_equipamento.php?cod=<?= $e['cod'] ?>"
                   class="btn-icone perigo" title="Eliminar"
                   onclick="return confirm('Eliminar este equipamento?')">
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
