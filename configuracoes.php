<?php
require_once("db/conexao.php");

$cod  = (int)$_SESSION['cod'];
$u    = mysqli_fetch_assoc(mysqli_query($con,"SELECT * FROM usuario WHERE cod=$cod"));
$erros= [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nome  = trim($_POST['nome']  ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($nome==='')  $erros[]='O nome é obrigatório.';
    if ($email==='') $erros[]='O e-mail é obrigatório.';
    if (empty($erros)) {
        $em = mysqli_real_escape_string($con,$email);
        $dup= mysqli_fetch_assoc(mysqli_query($con,"SELECT cod FROM usuario WHERE email='$em' AND cod!=$cod"));
        if ($dup) $erros[]='Este e-mail já está em uso por outro utilizador.';
    }
    if (empty($erros)) {
        $n = mysqli_real_escape_string($con,$nome);
        $e = mysqli_real_escape_string($con,$email);
        mysqli_query($con,"UPDATE usuario SET nome='$n',email='$e' WHERE cod=$cod");
        $_SESSION['nome']=$nome; $_SESSION['email']=$email;
        $u['nome']=$nome; $u['email']=$email;
        header("Location: configuracoes.php?msg=salvo"); exit;
    }
}

$msgs = ['salvo'=>['sucesso','Dados actualizados com sucesso!'],'senha'=>['sucesso','Palavra-passe alterada com sucesso!']];
[$mt,$mm] = $msgs[$_GET['msg']??''] ?? ['',''];

$p  = explode(' ',trim($u['nome']));
$av = strtoupper(substr($p[0],0,1)).(count($p)>1?strtoupper(substr(end($p),0,1)):'');

require_once("header.php");
?>
<main class="pagina" style="max-width:620px;">
  <div class="page-header">
    <div class="page-header-txt">
      <h1 class="pagina-titulo">Configurações</h1>
      <p class="pagina-subtitulo">Gerir as suas informações de conta</p>
    </div>
  </div>

  <?php if ($mm): ?>
  <div class="alerta alerta-<?= $mt ?> mb-20">
    <i class="material-icons-round"><?= $mt==='sucesso'?'check_circle':'error_outline' ?></i>
    <span><?= htmlspecialchars($mm) ?></span>
    <button data-fechar-alerta style="margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;">
      <i class="material-icons-round" style="font-size:18px;">close</i>
    </button>
  </div>
  <?php endif; ?>

  <!-- Perfil -->
  <div class="card mb-16">
    <div class="card-body" style="display:flex;align-items:center;gap:16px;">
      <div style="width:56px;height:56px;border-radius:50%;background:var(--azul);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:#fff;flex-shrink:0;"><?= $av ?></div>
      <div>
        <div style="font-size:17px;font-weight:700;color:var(--c900);"><?= htmlspecialchars($u['nome']) ?></div>
        <div style="font-size:13px;color:var(--c500);"><?= htmlspecialchars($u['email']) ?></div>
        <div style="margin-top:4px;">
          <span class="badge <?= (int)$u['perfil_cod']===1?'badge-azul':'badge-cinza' ?>">
            <?= (int)$u['perfil_cod']===1?'Administrador':'Utilizador' ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Editar dados -->
  <div class="card mb-16">
    <div class="card-header"><span class="card-titulo">Dados da conta</span></div>
    <form method="POST">
      <div class="card-body">
        <?php if (!empty($erros)): ?>
        <div class="alerta alerta-erro" style="margin-bottom:16px;"><i class="material-icons-round">error_outline</i>
          <div><?php foreach ($erros as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
        </div>
        <?php endif; ?>
        <div class="form-grupo"><label class="form-label">Nome completo</label>
          <input type="text" name="nome" class="form-input" value="<?= htmlspecialchars($u['nome']) ?>" required></div>
        <div class="form-grupo" style="margin-bottom:0;"><label class="form-label">E-mail</label>
          <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($u['email']) ?>" required></div>
      </div>
      <div class="card-footer" style="display:flex;justify-content:flex-end;">
        <button type="submit" class="btn btn-primario"><i class="material-icons-round">save</i> Guardar alterações</button>
      </div>
    </form>
  </div>

  <!-- Segurança -->
  <div class="card mb-16">
    <div class="card-header"><span class="card-titulo">Segurança</span></div>
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
      <div>
        <div style="font-size:14px;font-weight:500;color:var(--c800);">Palavra-passe de acesso</div>
        <div style="font-size:12px;color:var(--c500);">Altere a sua palavra-passe regularmente</div>
      </div>
      <a href="trocar_senha.php" class="btn btn-outline">
        <i class="material-icons-round">lock_reset</i> Alterar palavra-passe
      </a>
    </div>
  </div>

  <!-- Sessão -->
  <div class="card">
    <div class="card-header"><span class="card-titulo">Sessão</span></div>
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
      <div>
        <div style="font-size:14px;font-weight:500;color:var(--c800);">Terminar sessão</div>
        <div style="font-size:12px;color:var(--c500);">Encerra o acesso e redireciona para o login</div>
      </div>
      <a href="db/sair.php" class="btn btn-outline" style="color:var(--vermelho);border-color:var(--vermelho);">
        <i class="material-icons-round">logout</i> Terminar sessão
      </a>
    </div>
  </div>

</main>
<?php require_once("footer.php"); ?>
