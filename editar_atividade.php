<?php
require_once("db/conexao.php");

$cod    = (int)($_GET['cod'] ?? 0);
$editar = $cod > 0;
$a      = [];
$erros  = [];

if ($editar) {
    $r = mysqli_query($con, "SELECT * FROM atividades WHERE cod=$cod");
    if (!$r || mysqli_num_rows($r) === 0) { header("Location: visualizar_atividade.php?msg=erro"); exit; }
    $a = mysqli_fetch_assoc($r);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo     = trim($_POST['titulo']     ?? '');
    $aberto_por = trim($_POST['aberto_por'] ?? '');
    $observacao = trim($_POST['observacao'] ?? '');
    $fomentar   = trim($_POST['fomentar']   ?? '');
    $status     = $_POST['status']          ?? 'aberta';

    if ($titulo === '') $erros[] = 'O título é obrigatório.';

    if (empty($erros)) {
        $t  = mysqli_real_escape_string($con, $titulo);
        $ap = mysqli_real_escape_string($con, $aberto_por);
        $ob = mysqli_real_escape_string($con, $observacao);
        $fo = mysqli_real_escape_string($con, $fomentar);
        $st = mysqli_real_escape_string($con, $status);
        $uc = (int)$_SESSION['cod'];

        if ($editar) {
            $fech = ($st==='concluida' && $a['status']!=='concluida') ? ", data_fechamento=NOW()" : "";
            mysqli_query($con, "UPDATE atividades SET titulo='$t', aberto_por='$ap', observacao='$ob', fomentar='$fo', status='$st'$fech WHERE cod=$cod");
            header("Location: visualizar_atividade.php?msg=editada"); exit;
        } else {
            mysqli_query($con, "INSERT INTO atividades (titulo,aberto_por,observacao,fomentar,status,usuario_cod) VALUES ('$t','$ap','$ob','$fo','$st',$uc)");
            header("Location: visualizar_atividade.php?msg=criada"); exit;
        }
    }
    $a = ['titulo'=>$titulo,'aberto_por'=>$aberto_por,'observacao'=>$observacao,'fomentar'=>$fomentar,'status'=>$status];
}

$a['status'] = $a['status'] ?? 'aberta';
require_once("header.php");
function v($a,$k){ return htmlspecialchars($a[$k] ?? ''); }
function s($a,$k,$v){ return ($a[$k]??'')===$v?'selected':''; }
?>

<main class="pagina" style="max-width:720px;">

  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="visualizar_atividade.php" class="btn-icone"><i class="material-icons-round">arrow_back</i></a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo"><?= $editar?'Editar atividade':'Nova atividade' ?></h1>
        <p class="pagina-subtitulo"><?= $editar?'Actualizar dados do chamado':'Registar novo chamado ou atividade' ?></p>
      </div>
    </div>
  </div>

  <?php if (!empty($erros)): ?>
  <div class="alerta alerta-erro mb-20">
    <i class="material-icons-round">error_outline</i>
    <span><?= htmlspecialchars($erros[0]) ?></span>
  </div>
  <?php endif; ?>

  <form method="POST">
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Dados da atividade</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo col-span-full">
            <label class="form-label">Título <span class="obg">*</span></label>
            <input type="text" name="titulo" class="form-input"
                   placeholder="Descreva brevemente o chamado ou atividade"
                   value="<?= v($a,'titulo') ?>" required autofocus>
          </div>
          <div class="form-grupo">
            <label class="form-label">Aberto por</label>
            <input type="text" name="aberto_por" class="form-input"
                   placeholder="Nome de quem reportou"
                   value="<?= v($a,'aberto_por') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Responsável / Técnico</label>
            <input type="text" name="fomentar" class="form-input"
                   placeholder="Quem vai tratar ou acompanhar"
                   value="<?= v($a,'fomentar') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Estado</label>
            <select name="status" class="form-select">
              <option value="aberta"       <?= s($a,'status','aberta')       ?>>🟠 Em aberto</option>
              <option value="em_andamento" <?= s($a,'status','em_andamento') ?>>🟣 Em curso</option>
              <option value="concluida"    <?= s($a,'status','concluida')    ?>>🟢 Concluída</option>
            </select>
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Descrição / Observações</label>
            <textarea name="observacao" class="form-textarea" rows="5"
                      placeholder="Detalhe o problema, o que foi feito, próximos passos…"><?= v($a,'observacao') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;padding-bottom:24px;flex-wrap:wrap;gap:10px;">
      <?php if ($editar): ?>
      <a href="arquivar_atividade.php?cod=<?= $cod ?>&acao=arquivar"
         class="btn btn-outline" onclick="return confirm('Arquivar esta atividade?')">
        <i class="material-icons-round">archive</i> Arquivar
      </a>
      <?php else: ?>
      <a href="visualizar_atividade.php" class="btn btn-outline">Cancelar</a>
      <?php endif; ?>
      <button type="submit" class="btn btn-primario">
        <i class="material-icons-round"><?= $editar?'save':'add' ?></i>
        <?= $editar?'Guardar alterações':'Criar atividade' ?>
      </button>
    </div>
  </form>
</main>
<?php require_once("footer.php"); ?>
