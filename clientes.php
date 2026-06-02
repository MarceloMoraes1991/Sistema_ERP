<?php
require_once("db/conexao.php");

$porPagina   = 15;
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$offset      = ($paginaAtual - 1) * $porPagina;
$busca       = trim($_GET['busca']  ?? '');
$filtStatus  = $_GET['status']      ?? '';
$filtTipo    = $_GET['tipo']        ?? '';

$where = ['1=1'];
if ($busca !== '') {
    $b = mysqli_real_escape_string($con, $busca);
    $where[] = "(nome_completo LIKE '%$b%' OR cpf_cnpj LIKE '%$b%' OR email LIKE '%$b%' OR celular LIKE '%$b%' OR cidade LIKE '%$b%')";
}
if ($filtStatus !== '') $where[] = "status = '".mysqli_real_escape_string($con,$filtStatus)."'";
if ($filtTipo   !== '') $where[] = "tipo_pessoa = '".mysqli_real_escape_string($con,$filtTipo)."'";
$wh = implode(' AND ', $where);

$total    = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS t FROM clientes WHERE $wh"))['t'] ?? 0);
$totalPag = (int)ceil($total / $porPagina);
$clientes = mysqli_query($con,
    "SELECT * FROM clientes WHERE $wh ORDER BY nome_completo ASC LIMIT $offset, $porPagina");

$totais = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) AS total,
            SUM(status='ativo') AS ativos,
            SUM(status='inativo') AS inativos,
            SUM(tipo_pessoa='juridica') AS juridicos
     FROM clientes")) ?? [];

$msgs = [
    'criado'   => ['sucesso','Cliente criado com sucesso!'],
    'editado'  => ['sucesso','Cliente actualizado com sucesso!'],
    'excluido' => ['aviso',  'Cliente eliminado.'],
    'erro'     => ['erro',   'Ocorreu um erro. Tente novamente.'],
];
[$mt, $mm] = $msgs[$_GET['msg'] ?? ''] ?? ['',''];

require_once("header.php");
?>

<main class="pagina">

  <div class="page-header">
    <div class="page-header-txt">
      <h1 class="pagina-titulo">Clientes</h1>
      <p class="pagina-subtitulo">Gestão de clientes e contactos</p>
    </div>
    <div class="page-header-acoes">
      <a href="clientes_form.php" class="btn btn-primario">
        <i class="material-icons-round">person_add</i> Novo cliente
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
      <div class="metrica-label"><i class="material-icons-round">people</i> Total</div>
      <div class="metrica-valor"><?= number_format($totais['total'] ?? 0) ?></div>
      <div class="metrica-sub">Clientes registados</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-verde"></div>
      <div class="metrica-label"><i class="material-icons-round">person</i> Activos</div>
      <div class="metrica-valor"><?= number_format($totais['ativos'] ?? 0) ?></div>
      <div class="metrica-sub">Em actividade</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-roxo"></div>
      <div class="metrica-label"><i class="material-icons-round">business</i> Empresas</div>
      <div class="metrica-valor"><?= number_format($totais['juridicos'] ?? 0) ?></div>
      <div class="metrica-sub">Pessoas colectivas</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-vermelho"></div>
      <div class="metrica-label"><i class="material-icons-round">person_off</i> Inactivos</div>
      <div class="metrica-valor"><?= number_format($totais['inativos'] ?? 0) ?></div>
      <div class="metrica-sub">Desactivados</div>
    </div>
  </div>

  <div class="card">

    <!-- Filtros -->
    <form method="GET" class="filtros-bar">
      <div class="form-grupo">
        <label class="form-label">Pesquisar</label>
        <div style="position:relative;">
          <i class="material-icons-round" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--c400);font-size:16px;pointer-events:none;">search</i>
          <input type="text" name="busca" class="form-input"
                 style="padding-left:32px;width:280px;"
                 placeholder="Nome, NIF/NIPC, e-mail, localidade…"
                 value="<?= htmlspecialchars($busca) ?>">
        </div>
      </div>
      <div class="form-grupo">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select" style="width:170px;">
          <option value="">Todos</option>
          <option value="fisica"    <?= $filtTipo==='fisica'   ?'selected':'' ?>>Pessoa Singular</option>
          <option value="juridica"  <?= $filtTipo==='juridica' ?'selected':'' ?>>Pessoa Colectiva</option>
        </select>
      </div>
      <div class="form-grupo">
        <label class="form-label">Estado</label>
        <select name="status" class="form-select" style="width:130px;">
          <option value="">Todos</option>
          <option value="ativo"   <?= $filtStatus==='ativo'  ?'selected':'' ?>>Activo</option>
          <option value="inativo" <?= $filtStatus==='inativo'?'selected':'' ?>>Inactivo</option>
        </select>
      </div>
      <div class="form-grupo" style="align-self:flex-end;">
        <button type="submit" class="btn btn-primario">
          <i class="material-icons-round">filter_list</i> Filtrar
        </button>
      </div>
      <?php if ($busca || $filtStatus || $filtTipo): ?>
      <div class="form-grupo" style="align-self:flex-end;">
        <a href="clientes.php" class="btn btn-outline">
          <i class="material-icons-round">close</i> Limpar
        </a>
      </div>
      <?php endif; ?>
    </form>

    <div class="card-header" style="border-top:1px solid var(--c200);">
      <span class="card-titulo">
        <?= number_format($total) ?> <?= $total===1?'cliente encontrado':'clientes encontrados' ?>
      </span>
      <span style="font-size:12px;color:var(--c400);">
        Página <?= $paginaAtual ?>/<?= max(1,$totalPag) ?>
      </span>
    </div>

    <div class="tabela-wrap">
      <table class="tabela">
        <thead>
          <tr>
            <th>Cliente</th>
            <th>Tipo</th>
            <th>NIF / NIPC</th>
            <th>Contacto</th>
            <th>Localidade</th>
            <th>Estado</th>
            <th style="text-align:center;">Acções</th>
          </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($clientes) === 0): ?>
          <tr><td colspan="7">
            <div class="vazio">
              <i class="material-icons-round">people</i>
              <h3>Nenhum cliente encontrado</h3>
              <p>Ajuste os filtros ou crie um novo cliente.</p>
              <a href="clientes_form.php" class="btn btn-primario btn-sm">
                <i class="material-icons-round">person_add</i> Criar cliente
              </a>
            </div>
          </td></tr>
        <?php else: ?>
          <?php while ($c = mysqli_fetch_assoc($clientes)):
            $p  = explode(' ', trim($c['nome_completo']));
            $av = strtoupper(substr($p[0],0,1)).(count($p)>1?strtoupper(substr(end($p),0,1)):'');
            $is_ativo = $c['status'] === 'ativo';
            $is_jur   = $c['tipo_pessoa'] === 'juridica';
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:50%;
                            background:<?= $is_jur?'var(--roxo-claro)':'var(--azul-claro)' ?>;
                            display:flex;align-items:center;justify-content:center;
                            font-size:12px;font-weight:700;
                            color:<?= $is_jur?'var(--roxo)':'var(--azul)' ?>;flex-shrink:0;">
                  <?= $av ?>
                </div>
                <div>
                  <div class="td-nome"><?= htmlspecialchars($c['nome_completo']) ?></div>
                  <?php if ($c['email']): ?>
                  <div class="td-sub"><?= htmlspecialchars($c['email']) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td>
              <span class="badge <?= $is_jur?'badge-roxo':'badge-azul' ?>">
                <?= $is_jur ? 'Colectiva' : 'Singular' ?>
              </span>
            </td>
            <td style="font-size:12.5px;color:var(--c600);font-family:monospace;">
              <?= $c['cpf_cnpj'] ? htmlspecialchars($c['cpf_cnpj']) : '<span style="color:var(--c300);">—</span>' ?>
            </td>
            <td>
              <?php if ($c['celular']): ?>
              <div style="font-size:13px;"><?= htmlspecialchars($c['celular']) ?></div>
              <?php endif; ?>
              <?php if ($c['whatsapp'] && $c['whatsapp'] !== $c['celular']): ?>
              <div class="td-sub" style="display:flex;align-items:center;gap:3px;">
                <i class="material-icons-round" style="font-size:12px;color:#25D366;">chat</i>
                <?= htmlspecialchars($c['whatsapp']) ?>
              </div>
              <?php endif; ?>
              <?php if (!$c['celular'] && !$c['whatsapp']): ?>
              <span style="color:var(--c300);">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size:13px;color:var(--c600);">
              <?php
              $loc = array_filter([$c['cidade'], $c['estado']]);
              echo $loc ? htmlspecialchars(implode(', ', $loc)) : '<span style="color:var(--c300);">—</span>';
              ?>
            </td>
            <td>
              <span class="badge <?= $is_ativo?'badge-verde':'badge-cinza' ?>">
                <?= $is_ativo ? 'Activo' : 'Inactivo' ?>
              </span>
            </td>
            <td>
              <div class="acoes" style="justify-content:center;">
                <a href="clientes_detalhe.php?cod=<?= $c['cod'] ?>" class="btn-icone" title="Ver ficha">
                  <i class="material-icons-round" style="font-size:14px;">visibility</i>
                </a>
                <a href="orcamento_form.php?cliente_cod=<?= $c['cod'] ?>"
                   class="btn-icone" title="Criar orçamento" style="color:var(--ciano);">
                  <i class="material-icons-round" style="font-size:14px;">request_quote</i>
                </a>
                <a href="clientes_form.php?cod=<?= $c['cod'] ?>" class="btn-icone" title="Editar">
                  <i class="material-icons-round" style="font-size:14px;">edit</i>
                </a>
                <a href="clientes_excluir.php?cod=<?= $c['cod'] ?>"
                   class="btn-icone perigo" title="Eliminar"
                   onclick="return confirm('Eliminar o cliente <?= addslashes(htmlspecialchars($c['nome_completo'])) ?>?')">
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

    <!-- Paginação -->
    <?php if ($totalPag > 1):
      $qs = http_build_query(array_filter(['busca'=>$busca,'status'=>$filtStatus,'tipo'=>$filtTipo]));
      $qs = $qs ? "&$qs" : '';
    ?>
    <div class="paginacao">
      <?php if ($paginaAtual > 1): ?>
        <a href="?pagina=<?= $paginaAtual-1 ?><?= $qs ?>" class="pag-btn"><i class="material-icons-round">chevron_left</i></a>
      <?php endif; ?>
      <?php for ($p=max(1,$paginaAtual-2); $p<=min($totalPag,$paginaAtual+2); $p++): ?>
        <a href="?pagina=<?= $p ?><?= $qs ?>" class="pag-btn <?= $p===$paginaAtual?'ativo':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <?php if ($paginaAtual < $totalPag): ?>
        <a href="?pagina=<?= $paginaAtual+1 ?><?= $qs ?>" class="pag-btn"><i class="material-icons-round">chevron_right</i></a>
      <?php endif; ?>
      <span class="pag-info"><?= number_format($total) ?> registos · Pág. <?= $paginaAtual ?>/<?= $totalPag ?></span>
    </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once("footer.php"); ?>
