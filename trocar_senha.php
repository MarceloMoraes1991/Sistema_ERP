<?php
require_once("db/conexao.php");

$id_alvo = isset($_GET['id']) && (int)$_SESSION['perfil']===1
    ? (int)$_GET['id']
    : (int)$_SESSION['cod'];

$r = mysqli_query($con,"SELECT cod,nome,email FROM usuario WHERE cod=$id_alvo");
if (!$r || mysqli_num_rows($r)===0) { header("Location: dashboard.php"); exit; }
$u = mysqli_fetch_assoc($r);

$erros = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nova  = trim($_POST['nova_senha'] ?? '');
    $conf  = trim($_POST['confirmar']  ?? '');
    $atual = trim($_POST['senha_atual']?? '');

    if ($nova===''     ) $erros[]='Introduza a nova palavra-passe.';
    if (strlen($nova)<6) $erros[]='A palavra-passe deve ter no mínimo 6 caracteres.';
    if ($nova!==$conf  ) $erros[]='As palavras-passe não coincidem.';

    if ((int)$_SESSION['perfil']!==1) {
        $r2 = mysqli_fetch_assoc(mysqli_query($con,"SELECT senha FROM usuario WHERE cod=$id_alvo"));
        if (md5($atual)!==($r2['senha']??'')) $erros[]='Palavra-passe actual incorrecta.';
    }

    if (empty($erros)) {
        $h = mysqli_real_escape_string($con, md5($nova));
        mysqli_query($con,"UPDATE usuario SET senha='$h' WHERE cod=$id_alvo");
        $is_admin = (int)$_SESSION['perfil']===1;
        $is_proprio = $id_alvo===(int)$_SESSION['cod'];
        header("Location: ".($is_admin&&!$is_proprio?"lista_usuarios.php?msg=senha":"configuracoes.php?msg=senha"));
        exit;
    }
}

$is_proprio = $id_alvo===(int)$_SESSION['cod'];
$is_admin   = (int)$_SESSION['perfil']===1;
$p  = explode(' ',trim($u['nome']));
$av = strtoupper(substr($p[0],0,1)).(count($p)>1?strtoupper(substr(end($p),0,1)):'');

require_once("header.php");
?>
<main class="pagina" style="max-width:500px;">
  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="<?= $is_admin&&!$is_proprio?'lista_usuarios.php':'configuracoes.php' ?>" class="btn-icone">
        <i class="material-icons-round">arrow_back</i>
      </a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo">Alterar palavra-passe</h1>
        <p class="pagina-subtitulo"><?= $is_proprio?'Altere a sua palavra-passe de acesso':'Alterar palavra-passe de '.htmlspecialchars($u['nome']) ?></p>
      </div>
    </div>
  </div>

  <?php if (!empty($erros)): ?>
  <div class="alerta alerta-erro mb-20"><i class="material-icons-round">error_outline</i>
    <div><?php foreach ($erros as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
  </div>
  <?php endif; ?>

  <div class="card mb-16">
    <div class="card-body" style="display:flex;align-items:center;gap:12px;">
      <div style="width:40px;height:40px;border-radius:50%;background:var(--azul);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#fff;flex-shrink:0;"><?= $av ?></div>
      <div>
        <div style="font-weight:600;color:var(--c800);"><?= htmlspecialchars($u['nome']) ?></div>
        <div style="font-size:12px;color:var(--c500);"><?= htmlspecialchars($u['email']) ?></div>
      </div>
    </div>
  </div>

  <form method="POST">
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Nova palavra-passe</span></div>
      <div class="card-body">
        <div class="form-grid" style="gap:16px;">
          <?php if ($is_proprio && !$is_admin): ?>
          <div class="form-grupo" style="margin-bottom:0;">
            <label class="form-label">Palavra-passe actual <span class="obg">*</span></label>
            <div style="position:relative;">
              <input type="password" name="senha_atual" id="s1" class="form-input" placeholder="••••••••" style="padding-right:42px;" required>
              <button type="button" onclick="toggle('s1','o1')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--c400);cursor:pointer;display:flex;align-items:center;">
                <i class="material-icons-round" id="o1" style="font-size:18px;">visibility</i>
              </button>
            </div>
          </div>
          <?php endif; ?>
          <div class="form-grupo" style="margin-bottom:0;">
            <label class="form-label">Nova palavra-passe <span class="obg">*</span></label>
            <div style="position:relative;">
              <input type="password" name="nova_senha" id="s2" class="form-input" placeholder="Mínimo 6 caracteres" style="padding-right:42px;" minlength="6" required>
              <button type="button" onclick="toggle('s2','o2')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--c400);cursor:pointer;display:flex;align-items:center;">
                <i class="material-icons-round" id="o2" style="font-size:18px;">visibility</i>
              </button>
            </div>
          </div>
          <div class="form-grupo" style="margin-bottom:0;">
            <label class="form-label">Confirmar palavra-passe <span class="obg">*</span></label>
            <input type="password" name="confirmar" class="form-input" placeholder="Repita a nova palavra-passe" minlength="6" required>
          </div>
        </div>
      </div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:10px;padding-bottom:24px;">
      <a href="<?= $is_admin&&!$is_proprio?'lista_usuarios.php':'configuracoes.php' ?>" class="btn btn-outline">Cancelar</a>
      <button type="submit" class="btn btn-primario"><i class="material-icons-round">lock_reset</i> Alterar palavra-passe</button>
    </div>
  </form>
</main>
<script>
function toggle(inputId,iconId){
  const i=document.getElementById(inputId),ic=document.getElementById(iconId);
  if(!i)return; i.type=i.type==='password'?'text':'password';
  if(ic)ic.textContent=i.type==='password'?'visibility':'visibility_off';
}
</script>
<?php require_once("footer.php"); ?>
