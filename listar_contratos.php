<?php
require_once("db/conexao.php");

$busca = trim($_GET['busca'] ?? '');
$where = ['1=1'];
if ($busca !== '') {
    $b = mysqli_real_escape_string($con, $busca);
    $where[] = "(titulo LIKE '%$b%' OR descricao LIKE '%$b%')";
}
$wh = implode(' AND ', $where);

$contratos = mysqli_query($con, "SELECT * FROM contratos WHERE $wh ORDER BY criado_em DESC");
$total     = (int)(mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) AS t FROM contratos WHERE $wh"))['t'] ?? 0);

$msgs = [
    'enviado'  => ['sucesso','Documento enviado com sucesso!'],
    'excluido' => ['aviso',  'Documento removido.'],
];
[$mt, $mm] = $msgs[$_GET['msg'] ?? ''] ?? ['',''];

require_once("header.php");

function icone_doc($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match($ext) {
        'pdf'        => ['picture_as_pdf', 'var(--vermelho)',  'var(--vermelho-claro)'],
        'doc','docx' => ['description',    'var(--azul)',      'var(--azul-claro)'],
        'xls','xlsx' => ['table_chart',    'var(--verde)',     'var(--verde-claro)'],
        'jpg','jpeg','png','gif' => ['image','var(--roxo)',    'var(--roxo-claro)'],
        'zip','rar'  => ['folder_zip',     'var(--laranja)',   'var(--laranja-claro)'],
        default      => ['insert_drive_file','var(--c600)',    'var(--c100)'],
    };
}
?>

<main class="pagina">
  <div class="page-header">
    <div class="page-header-txt">
      <h1 class="pagina-titulo">Documentos / Contratos</h1>
      <p class="pagina-subtitulo">Arquivos e documentos da empresa</p>
    </div>
    <div class="page-header-acoes">
      <a href="cad_contrato.php" class="btn btn-primario">
        <i class="material-icons-round">upload_file</i> Enviar documento
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

  <div class="card">
    <form method="GET" class="filtros-bar">
      <div class="form-grupo">
        <label class="form-label">Pesquisar</label>
        <div style="position:relative;">
          <i class="material-icons-round" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--c400);font-size:16px;pointer-events:none;">search</i>
          <input type="text" name="busca" class="form-input" style="padding-left:32px;width:280px;"
                 placeholder="Título ou descrição…" value="<?= htmlspecialchars($busca) ?>">
        </div>
      </div>
      <div class="form-grupo" style="align-self:flex-end;">
        <button type="submit" class="btn btn-primario"><i class="material-icons-round">search</i> Pesquisar</button>
      </div>
      <?php if ($busca): ?>
      <div class="form-grupo" style="align-self:flex-end;">
        <a href="listar_contratos.php" class="btn btn-outline"><i class="material-icons-round">close</i> Limpar</a>
      </div>
      <?php endif; ?>
    </form>

    <div class="card-header" style="border-top:1px solid var(--c200);">
      <span class="card-titulo"><?= number_format($total) ?> documento(s)</span>
    </div>

    <?php if (mysqli_num_rows($contratos) === 0): ?>
    <div class="vazio">
      <i class="material-icons-round">folder_open</i>
      <h3>Nenhum documento encontrado</h3>
      <p>Envie o primeiro documento ou contrato.</p>
      <a href="cad_contrato.php" class="btn btn-primario btn-sm">
        <i class="material-icons-round">upload_file</i> Enviar documento
      </a>
    </div>
    <?php else: ?>
    <div class="tabela-wrap">
      <table class="tabela">
        <thead>
          <tr><th>Documento</th><th>Descrição</th><th>Enviado em</th><th style="text-align:center;">Acções</th></tr>
        </thead>
        <tbody>
        <?php while ($cont = mysqli_fetch_assoc($contratos)):
          $arq = $cont['arquivo'] ?? '';
          [$ic, $cor, $bg] = icone_doc($arq);
          $ext = strtoupper(pathinfo($arq, PATHINFO_EXTENSION));
        ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:36px;height:36px;border-radius:var(--r-sm);background:<?= $bg ?>;
                          display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="material-icons-round" style="font-size:18px;color:<?= $cor ?>;"><?= $ic ?></i>
              </div>
              <div>
                <div class="td-nome"><?= htmlspecialchars($cont['titulo']) ?></div>
                <?php if ($ext): ?><div class="td-sub"><?= $ext ?></div><?php endif; ?>
              </div>
            </div>
          </td>
          <td style="font-size:13px;color:var(--c600);">
            <?= $cont['descricao'] ? htmlspecialchars(mb_strimwidth($cont['descricao'],0,70,'…')) : '<span style="color:var(--c300);">—</span>' ?>
          </td>
          <td style="font-size:12px;color:var(--c500);white-space:nowrap;">
            <?= date('d/m/Y', strtotime($cont['criado_em'])) ?>
          </td>
          <td>
            <div class="acoes" style="justify-content:center;">
              <?php if ($arq): ?>
              <a href="<?= htmlspecialchars($arq) ?>" target="_blank"
                 class="btn-icone" title="Abrir" style="color:var(--azul);">
                <i class="material-icons-round" style="font-size:14px;">open_in_new</i>
              </a>
              <a href="<?= htmlspecialchars($arq) ?>" download
                 class="btn-icone" title="Transferir" style="color:var(--verde);">
                <i class="material-icons-round" style="font-size:14px;">download</i>
              </a>
              <?php endif; ?>
              <a href="excluir_contrato.php?cod=<?= $cont['cod'] ?>"
                 class="btn-icone perigo" title="Eliminar"
                 onclick="return confirm('Eliminar «<?= addslashes(htmlspecialchars($cont['titulo'])) ?>»?')">
                <i class="material-icons-round" style="font-size:14px;">delete</i>
              </a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</main>
<?php require_once("footer.php"); ?>
