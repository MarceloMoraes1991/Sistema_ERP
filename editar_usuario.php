<?php
require_once("db/conexao.php");
if ((int)$_SESSION['perfil'] !== 1) { header("Location: dashboard.php"); exit; }

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) { header("Location: lista_usuarios.php"); exit; }
$r  = mysqli_query($con,"SELECT * FROM usuario WHERE cod=$id");
if (!$r || mysqli_num_rows($r)===0) { header("Location: lista_usuarios.php"); exit; }
$u  = mysqli_fetch_assoc($r);
$erros = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nome   = trim($_POST['nome']  ?? '');
    $email  = trim($_POST['email'] ?? '');
    $perfil = (int)($_POST['perfil']??2);

    if ($nome==='' ) $erros[]='O nome é obrigatório.';
    if ($email==='') $erros[]='O e-mail é obrigatório.';
    if (empty($erros)) {
        $em = mysqli_real_escape_string($con,$email);
        $dup= mysqli_fetch_assoc(mysqli_query($con,"SELECT cod FROM usuario WHERE email='$em' AND cod!=$id"));
        if ($dup) $erros[]='Este e-mail já está em uso.';
    }
    if (empty($erros)) {
        $n = mysqli_real_escape_string($con,$nome);
        $e = mysqli_real_escape_string($con,$email);
        mysqli_query($con,"UPDATE usuario SET nome='$n',email='$e',perfil_cod=$perfil WHERE cod=$id");
        header("Location: lista_usuarios.php?msg=editado"); exit;
    }
    $u = array_merge($u,['nome'=>$nome,'email'=>$email,'perfil_cod'=>$perfil]);
}

require_once("header.php");
?>
<main class="pagina" style="max-width:540px;">
  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="lista_usuarios.php" class="btn-icone"><i class="material-icons-round">arrow_back</i></a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo">Editar utilizador</h1>
        <p class="pagina-subtitulo">Alterar dados de <?= htmlspecialchars($u['nome']) ?></p>
      </div>
    </div>
  </div>
  <?php if (!empty($erros)): ?>
  <div class="alerta alerta-erro mb-20"><i class="material-icons-round">error_outline</i>
    <div><?php foreach ($erros as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
  </div>
  <?php endif; ?>
  <form method="POST">
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Dados do utilizador</span></div>
      <div class="card-body">
        <div class="form-grupo"><label class="form-label">Nome <span class="obg">*</span></label>
          <input type="text" name="nome" class="form-input" value="<?= htmlspecialchars($u['nome']) ?>" required></div>
        <div class="form-grupo"><label class="form-label">E-mail <span class="obg">*</span></label>
          <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($u['email']) ?>" required></div>
        <div class="form-grupo" style="margin-bottom:0;"><label class="form-label">Perfil</label>
          <select name="perfil" class="form-select">
            <option value="2" <?= (int)$u['perfil_cod']===2?'selected':'' ?>>Utilizador (acesso normal)</option>
            <option value="1" <?= (int)$u['perfil_cod']===1?'selected':'' ?>>Administrador (acesso total)</option>
          </select></div>
      </div>
    </div>
    <div style="display:flex;justify-content:space-between;gap:10px;padding-bottom:24px;flex-wrap:wrap;">
      <a href="trocar_senha.php?id=<?= $id ?>" class="btn btn-outline">
        <i class="material-icons-round">lock_reset</i> Alterar palavra-passe
      </a>
      <div style="display:flex;gap:10px;">
        <a href="lista_usuarios.php" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primario"><i class="material-icons-round">save</i> Guardar</button>
      </div>
    </div>
  </form>
</main>
<?php require_once("footer.php"); ?>
