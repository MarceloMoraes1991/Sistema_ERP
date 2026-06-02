<?php
require_once("db/conexao.php");

$cod    = (int)($_GET['cod'] ?? 0);
$editar = $cod > 0;
$ch     = [];
$erros  = [];

if ($editar) {
    $r = mysqli_query($con, "SELECT * FROM chips WHERE cod=$cod");
    if (!$r || mysqli_num_rows($r) === 0) { header("Location: chips.php"); exit; }
    $ch = mysqli_fetch_assoc($r);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome']      ?? '');
    $numero    = trim($_POST['numero']    ?? '');
    $operadora = trim($_POST['operadora'] ?? '');
    $qrcode    = trim($_POST['qrcode']    ?? '');
    $status    = $_POST['status']         ?? 'ativo';

    if ($nome === '') $erros[] = 'O nome/identificação é obrigatório.';

    if (empty($erros)) {
        $n  = mysqli_real_escape_string($con, $nome);
        $nu = mysqli_real_escape_string($con, $numero);
        $op = mysqli_real_escape_string($con, $operadora);
        $qr = mysqli_real_escape_string($con, $qrcode);
        $st = mysqli_real_escape_string($con, $status);

        if ($editar) {
            mysqli_query($con, "UPDATE chips SET nome='$n', numero='$nu', operadora='$op', qrcode='$qr', status='$st' WHERE cod=$cod");
            header("Location: chips.php?msg=editado"); exit;
        } else {
            mysqli_query($con, "INSERT INTO chips (nome,numero,operadora,qrcode,status) VALUES ('$n','$nu','$op','$qr','$st')");
            header("Location: chips.php?msg=criado"); exit;
        }
    }
    $ch = ['nome'=>$nome,'numero'=>$numero,'operadora'=>$operadora,'qrcode'=>$qrcode,'status'=>$status];
}

$ch['status'] = $ch['status'] ?? 'ativo';

require_once("header.php");
function v($c,$k){ return htmlspecialchars($c[$k] ?? ''); }
function s($c,$k,$v){ return ($c[$k]??'')===$v?'selected':''; }
?>

<main class="pagina" style="max-width:620px;">
  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="chips.php" class="btn-icone"><i class="material-icons-round">arrow_back</i></a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo"><?= $editar?'Editar chip':'Novo chip' ?></h1>
        <p class="pagina-subtitulo"><?= $editar?'Actualizar dados do cartão SIM':'Registar novo cartão SIM ou operadora' ?></p>
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
      <div class="card-header"><span class="card-titulo">Dados do chip</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo col-span-full">
            <label class="form-label">Nome / Identificação <span class="obg">*</span></label>
            <input type="text" name="nome" class="form-input"
                   placeholder="Ex: WhatsApp Suporte, Chip Técnico, João Silva"
                   value="<?= v($ch,'nome') ?>" required>
          </div>
          <div class="form-grupo">
            <label class="form-label">Número</label>
            <input type="text" name="numero" class="form-input"
                   placeholder="9xx xxx xxx"
                   value="<?= v($ch,'numero') ?>" maxlength="15">
            <div class="form-hint">Link WhatsApp gerado automaticamente com prefixo +351</div>
          </div>
          <div class="form-grupo">
            <label class="form-label">Operadora</label>
            <input type="text" name="operadora" class="form-input"
                   placeholder="Ex: MEO, NOS, Vodafone, NOWO"
                   value="<?= v($ch,'operadora') ?>" list="operadoras-lista">
            <datalist id="operadoras-lista">
              <option value="MEO"><option value="NOS"><option value="Vodafone">
              <option value="NOWO"><option value="Lycamobile">
            </datalist>
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">QR Code / Dados adicionais</label>
            <textarea name="qrcode" class="form-textarea" rows="3"
                      placeholder="Cole aqui o QR Code ou informações extras do cartão SIM…"><?= v($ch,'qrcode') ?></textarea>
          </div>
          <div class="form-grupo">
            <label class="form-label">Estado</label>
            <select name="status" class="form-select">
              <option value="ativo"     <?= s($ch,'status','ativo')     ?>>Activo</option>
              <option value="arquivado" <?= s($ch,'status','arquivado') ?>>Arquivado</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;padding-bottom:24px;">
      <a href="chips.php" class="btn btn-outline">Cancelar</a>
      <button type="submit" class="btn btn-primario">
        <i class="material-icons-round"><?= $editar?'save':'add' ?></i>
        <?= $editar?'Guardar alterações':'Registar chip' ?>
      </button>
    </div>
  </form>
</main>
<?php require_once("footer.php"); ?>
