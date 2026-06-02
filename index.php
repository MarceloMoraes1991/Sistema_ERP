<?php
session_start();
if (isset($_SESSION['email'])) { header("Location: dashboard.php"); exit; }

$erros = [
    1 => ['erro',    'Acesso negado. Verifique as suas permissões.'],
    2 => ['erro',    'E-mail ou palavra-passe incorrectos. Tente novamente.'],
    3 => ['sucesso', 'Sessão terminada com sucesso.'],
];
$msg = $erros[(int)($_GET['erro'] ?? 0)] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ITM Technology — Acesso</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/sistema.css">
  <style>
    .login-page {
      background: var(--c900);
      background-image:
        radial-gradient(ellipse at 20% 50%, rgba(37,99,235,.15) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 20%, rgba(14,165,233,.10) 0%, transparent 50%);
    }
    .login-card { border-top: 3px solid var(--azul); }
    .login-marca {
      display: flex; flex-direction: column; align-items: center; gap: 6px;
      margin-bottom: 28px;
    }
    .login-marca img { height: 56px; }
    .login-marca-nome {
      font-size: 11px; font-weight: 600; color: var(--c400);
      letter-spacing: .12em; text-transform: uppercase;
    }
  </style>
</head>
<body>

<div class="login-page">
  <div class="login-card">

    <div class="login-marca">
      <img src="assets/img/logo.svg" alt="ITM Technology"
           onerror="this.style.display='none';document.getElementById('nome-fallback').style.display='block'">
      <div id="nome-fallback" style="display:none;font-size:24px;font-weight:800;color:var(--azul);">ITM</div>
      <span class="login-marca-nome">Sistema de Gestão</span>
    </div>

    <h1 class="login-titulo">Bem-vindo</h1>
    <p class="login-subtitulo">Introduza as suas credenciais para aceder</p>

    <?php if ($msg): ?>
    <div class="alerta alerta-<?= $msg[0] ?>">
      <i class="material-icons-round"><?= $msg[0]==='sucesso'?'check_circle':'error_outline' ?></i>
      <span><?= htmlspecialchars($msg[1]) ?></span>
    </div>
    <?php endif; ?>

    <form action="db/verifica_login.php" method="POST" id="form-login">
      <div class="form-grupo">
        <label class="form-label" for="login">E-mail</label>
        <input class="form-input" type="email" name="login" id="login"
               placeholder="utilizador@itm.pt"
               required autocomplete="email" autofocus>
      </div>

      <div class="form-grupo" style="margin-bottom:22px;">
        <label class="form-label" for="senha">Palavra-passe</label>
        <div style="position:relative;">
          <input class="form-input" type="password" name="senha" id="senha"
                 placeholder="••••••••"
                 required autocomplete="current-password"
                 style="padding-right:44px;">
          <button type="button" id="toggle-senha"
                  style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                         background:none;border:none;color:var(--c400);cursor:pointer;
                         display:flex;align-items:center;padding:4px;">
            <i class="material-icons-round" id="icon-olho" style="font-size:18px;">visibility</i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primario"
              style="width:100%;justify-content:center;padding:11px;font-size:14px;">
        Entrar no sistema
        <i class="material-icons-round">arrow_forward</i>
      </button>
    </form>

    <p style="text-align:center;margin-top:24px;font-size:11.5px;color:var(--c400);">
      © <?= date('Y') ?> ITM Technology · Todos os direitos reservados
    </p>

  </div>
</div>

<script>
document.getElementById('toggle-senha').addEventListener('click', function () {
  const input = document.getElementById('senha');
  const icon  = document.getElementById('icon-olho');
  input.type  = input.type === 'password' ? 'text' : 'password';
  icon.textContent = input.type === 'password' ? 'visibility' : 'visibility_off';
});
</script>
</body>
</html>
