<?php
// cadastro.php — Novo utilizador
require_once("db/conexao.php");
if ((int)$_SESSION['perfil'] !== 1) { header("Location: dashboard.php"); exit; }

$erros = []; $dados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome   = trim($_POST['nome']   ?? '');
    $email  = trim($_POST['email']  ?? '');
    $senha  = trim($_POST['senha']  ?? '');
    $senha2 = trim($_POST['senha2'] ?? '');
    $perfil = (int)($_POST['perfil']?? 2);

    if ($nome===''     ) $erros[]='O nome é obrigatório.';
    if ($email===''    ) $erros[]='O e-mail é obrigatório.';
    if ($senha===''    ) $erros[]='A palavra-passe é obrigatória.';
    if ($senha!==$senha2)$erros[]='As palavras-passe não coincidem.';
    if (strlen($senha)<6)$erros[]='A palavra-passe deve ter no mínimo 6 caracteres.';

    if (empty($erros)) {
        $em = mysqli_real_escape_string($con, $email);
        $dup= mysqli_fetch_assoc(mysqli_query($con,"SELECT cod FROM usuario WHERE email='$em'"));
        if ($dup) $erros[]='Este e-mail já está registado.';
    }

    if (empty($erros)) {
        $n = mysqli_real_escape_string($con,$nome);
        $e = mysqli_real_escape_string($con,$email);
        $s = mysqli_real_escape_string($con, md5($senha));
        mysqli_query($con,"INSERT INTO usuario (nome,email,senha,perfil_cod) VALUES ('$n','$e','$s',$perfil)");
        header("Location: lista_usuarios.php?msg=criado"); exit;
    }
    $dados = compact('nome','email','perfil');
}

require_once("header.php");
function v($d,$k){ return htmlspecialchars($d[$k]??''); }
?>
<main class="pagina" style="max-width:560px;">
  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="lista_usuarios.php" class="btn-icone"><i class="material-icons-round">arrow_back</i></a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo">Novo utilizador</h1>
        <p class="pagina-subtitulo">Criar acesso ao sistema</p>
      </div>
    </div>
  </div>

  <?php if (!empty($erros)): ?>
  <div class="alerta alerta-erro mb-20"><i class="material-icons-round">error_outline</i>
    <div><strong>Corrija os erros:</strong><ul style="margin:4px 0 0 16px;padding:0;">
      <?php foreach ($erros as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul></div></div>
  <?php endif; ?>

  <form method="POST">
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Dados de acesso</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo col-span-full">
            <label class="form-label">Nome completo <span class="obg">*</span></label>
            <input type="text" name="nome" class="form-input" placeholder="Nome do utilizador" value="<?= v($dados,'nome') ?>" required>
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">E-mail <span class="obg">*</span></label>
            <input type="email" name="email" class="form-input" placeholder="email@itm.pt" value="<?= v($dados,'email') ?>" required>
          </div>
          <div class="form-grupo">
            <label class="form-label">Palavra-passe <span class="obg">*</span></label>
            <input type="password" name="senha" class="form-input" placeholder="Mínimo 6 caracteres" minlength="6">
          </div>
          <div class="form-grupo">
            <label class="form-label">Confirmar <span class="obg">*</span></label>
            <input type="password" name="senha2" class="form-input" placeholder="Repita a palavra-passe" minlength="6">
          </div>
          <div class="form-grupo" style="margin-bottom:0;">
            <label class="form-label">Perfil de acesso</label>
            <select name="perfil" class="form-select">
              <option value="2" <?= ($dados['perfil']??2)==2?'selected':'' ?>>Utilizador (acesso normal)</option>
              <option value="1" <?= ($dados['perfil']??2)==1?'selected':'' ?>>Administrador (acesso total)</option>
            </select>
          </div>
        </div>
      </div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:10px;padding-bottom:24px;">
      <a href="lista_usuarios.php" class="btn btn-outline">Cancelar</a>
      <button type="submit" class="btn btn-primario"><i class="material-icons-round">person_add</i> Criar utilizador</button>
    </div>
  </form>
</main>
<?php require_once("footer.php"); ?>
