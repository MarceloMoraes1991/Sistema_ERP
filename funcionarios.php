<?php
require_once("db/conexao.php");

$porPagina   = 15;
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$offset      = ($paginaAtual - 1) * $porPagina;
$busca       = trim($_GET['busca']  ?? '');
$filtStatus  = $_GET['status']      ?? '';
$filtDepto   = trim($_GET['depto']  ?? '');

$where = ['1=1'];
if ($busca !== '') {
    $b = mysqli_real_escape_string($con, $busca);
    $where[] = "(nome LIKE '%$b%' OR cargo LIKE '%$b%' OR departamento LIKE '%$b%' OR email LIKE '%$b%' OR nif LIKE '%$b%')";
}
if ($filtStatus !== '') $where[] = "status='".mysqli_real_escape_string($con,$filtStatus)."'";
if ($filtDepto  !== '') $where[] = "departamento='".mysqli_real_escape_string($con,$filtDepto)."'";
$wh = implode(' AND ', $where);

$total    = (int)(mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) AS t FROM funcionarios WHERE $wh"))['t'] ?? 0);
$totalPag = (int)ceil($total / $porPagina);
$lista    = mysqli_query($con,"SELECT * FROM funcionarios WHERE $wh ORDER BY nome ASC LIMIT $offset,$porPagina");

$tot = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) AS total,
            SUM(status='ativo') AS ativos,
            SUM(status='inativo') AS inativos,
            SUM(status='ferias') AS ferias,
            SUM(status='baixa') AS baixa
     FROM funcionarios")) ?? [];

// Departamentos distintos para filtro
$deptos = mysqli_query($con,"SELECT DISTINCT departamento FROM funcionarios WHERE departamento IS NOT NULL AND departamento!='' ORDER BY departamento");

$msgs = [
    'criado'   => ['sucesso','Funcionário registado com sucesso!'],
    'editado'  => ['sucesso','Funcionário actualizado com sucesso!'],
    'excluido' => ['aviso',  'Funcionário removido.'],
];
[$mt, $mm] = $msgs[$_GET['msg'] ?? ''] ?? ['',''];

$label_status = [
    'ativo'   => ['badge-verde',   'Activo'],
    'inativo' => ['badge-cinza',   'Inactivo'],
    'ferias'  => ['badge-azul',    'Férias'],
    'baixa'   => ['badge-laranja', 'Baixa'],
];

require_once("header.php");
?>

<main class="pagina">

  <div class="page-header">
    <div class="page-header-txt">
      <h1 class="pagina-titulo">Funcionários</h1>
      <p class="pagina-subtitulo">Gestão de colaboradores da ITM Technology</p>
    </div>
    <div class="page-header-acoes">
      <a href="funcionarios_form.php" class="btn btn-primario">
        <i class="material-icons-round">person_add</i> Novo funcionário
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
      <div class="metrica-label"><i class="material-icons-round">badge</i> Total</div>
      <div class="metrica-valor"><?= number_format($tot['total'] ?? 0) ?></div>
      <div class="metrica-sub">Colaboradores registados</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-verde"></div>
      <div class="metrica-label"><i class="material-icons-round">check_circle</i> Activos</div>
      <div class="metrica-valor"><?= number_format($tot['ativos'] ?? 0) ?></div>
      <div class="metrica-sub">Em funções</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-ciano"></div>
      <div class="metrica-label"><i class="material-icons-round">beach_access</i> Férias</div>
      <div class="metrica-valor"><?= number_format($tot['ferias'] ?? 0) ?></div>
      <div class="metrica-sub">De férias</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-laranja"></div>
      <div class="metrica-label"><i class="material-icons-round">sick</i> Baixa</div>
      <div class="metrica-valor"><?= number_format($tot['baixa'] ?? 0) ?></div>
      <div class="metrica-sub">Em baixa médica</div>
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
                 placeholder="Nome, cargo, departamento, NIF…"
                 value="<?= htmlspecialchars($busca) ?>">
        </div>
      </div>
      <div class="form-grupo">
        <label class="form-label">Estado</label>
        <select name="status" class="form-select" style="width:140px;">
          <option value="">Todos</option>
          <option value="ativo"   <?= $filtStatus==='ativo'  ?'selected':'' ?>>Activo</option>
          <option value="inativo" <?= $filtStatus==='inativo'?'selected':'' ?>>Inactivo</option>
          <option value="ferias"  <?= $filtStatus==='ferias' ?'selected':'' ?>>Férias</option>
          <option value="baixa"   <?= $filtStatus==='baixa'  ?'selected':'' ?>>Baixa</option>
        </select>
      </div>
      <div class="form-grupo">
        <label class="form-label">Departamento</label>
        <select name="depto" class="form-select" style="width:170px;">
          <option value="">Todos</option>
          <?php while ($d = mysqli_fetch_assoc($deptos)): ?>
          <option value="<?= htmlspecialchars($d['departamento']) ?>"
                  <?= $filtDepto===$d['departamento']?'selected':'' ?>>
            <?= htmlspecialchars($d['departamento']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-grupo" style="align-self:flex-end;">
        <button type="submit" class="btn btn-primario">
          <i class="material-icons-round">filter_list</i> Filtrar
        </button>
      </div>
      <?php if ($busca || $filtStatus || $filtDepto): ?>
      <div class="form-grupo" style="align-self:flex-end;">
        <a href="funcionarios.php" class="btn btn-outline">
          <i class="material-icons-round">close</i> Limpar
        </a>
      </div>
      <?php endif; ?>
    </form>

    <div class="card-header" style="border-top:1px solid var(--c200);">
      <span class="card-titulo">
        <?= number_format($total) ?> <?= $total===1?'funcionário':'funcionários' ?>
      </span>
      <span style="font-size:12px;color:var(--c400);">
        Pág. <?= $paginaAtual ?>/<?= max(1,$totalPag) ?>
      </span>
    </div>

    <div class="tabela-wrap">
      <table class="tabela">
        <thead>
          <tr>
            <th>Colaborador</th>
            <th>Cargo</th>
            <th>Departamento</th>
            <th>Contacto</th>
            <th>Admissão</th>
            <th>Contrato</th>
            <th>Estado</th>
            <th style="text-align:center;">Acções</th>
          </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($lista) === 0): ?>
          <tr><td colspan="8">
            <div class="vazio">
              <i class="material-icons-round">badge</i>
              <h3>Nenhum funcionário encontrado</h3>
              <p>Ajuste os filtros ou registe um novo colaborador.</p>
              <a href="funcionarios_form.php" class="btn btn-primario btn-sm">
                <i class="material-icons-round">person_add</i> Registar funcionário
              </a>
            </div>
          </td></tr>
        <?php else: ?>
          <?php while ($f = mysqli_fetch_assoc($lista)):
            $p  = explode(' ', trim($f['nome']));
            $av = strtoupper(substr($p[0],0,1)).(count($p)>1?strtoupper(substr(end($p),0,1)):'');
            [$badge, $label] = $label_status[$f['status']] ?? ['badge-cinza', ucfirst($f['status'])];
            $tipo_contrato_map = [
              'efetivo'           => 'Efectivo',
              'termo_certo'       => 'Termo certo',
              'termo_incerto'     => 'Termo incerto',
              'prestacao_servicos'=> 'Prest. serviços',
              'estagio'           => 'Estágio',
              'outro'             => 'Outro',
            ];
            $tipo_label = $tipo_contrato_map[$f['tipo_contrato']] ?? '—';
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:50%;
                            background:var(--azul);
                            display:flex;align-items:center;justify-content:center;
                            font-size:12px;font-weight:700;color:#fff;flex-shrink:0;">
                  <?= $av ?>
                </div>
                <div>
                  <div class="td-nome"><?= htmlspecialchars($f['nome']) ?></div>
                  <?php if ($f['email']): ?>
                  <div class="td-sub"><?= htmlspecialchars($f['email']) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td style="font-size:13px;color:var(--c700);">
              <?= $f['cargo'] ? htmlspecialchars($f['cargo']) : '<span style="color:var(--c300);">—</span>' ?>
            </td>
            <td style="font-size:13px;color:var(--c600);">
              <?= $f['departamento'] ? htmlspecialchars($f['departamento']) : '<span style="color:var(--c300);">—</span>' ?>
            </td>
            <td>
              <?php if ($f['telemovel']): ?>
              <div style="font-size:13px;"><?= htmlspecialchars($f['telemovel']) ?></div>
              <?php elseif ($f['telefone']): ?>
              <div style="font-size:13px;"><?= htmlspecialchars($f['telefone']) ?></div>
              <?php else: ?>
              <span style="color:var(--c300);">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:var(--c500);">
              <?= $f['data_admissao'] ? date('d/m/Y', strtotime($f['data_admissao'])) : '<span style="color:var(--c300);">—</span>' ?>
            </td>
            <td>
              <span class="badge badge-cinza" style="font-size:10.5px;"><?= $tipo_label ?></span>
            </td>
            <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
            <td>
              <div class="acoes" style="justify-content:center;">
                <a href="funcionarios_detalhe.php?cod=<?= $f['cod'] ?>" class="btn-icone" title="Ver ficha">
                  <i class="material-icons-round" style="font-size:14px;">visibility</i>
                </a>
                <a href="funcionarios_form.php?cod=<?= $f['cod'] ?>" class="btn-icone" title="Editar">
                  <i class="material-icons-round" style="font-size:14px;">edit</i>
                </a>
                <a href="funcionarios_excluir.php?cod=<?= $f['cod'] ?>"
                   class="btn-icone perigo" title="Eliminar"
                   onclick="return confirm('Eliminar o funcionário <?= addslashes(htmlspecialchars($f['nome'])) ?>?')">
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
      $qs = http_build_query(array_filter(['busca'=>$busca,'status'=>$filtStatus,'depto'=>$filtDepto]));
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
