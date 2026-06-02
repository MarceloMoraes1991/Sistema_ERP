<?php
require_once("db/conexao.php");
if ((int)$_SESSION['perfil'] !== 1) { header("Location: dashboard.php"); exit; }

$busca = trim($_GET['busca'] ?? '');
$where = ['1=1'];
if ($busca !== '') {
    $b = mysqli_real_escape_string($con, $busca);
    $where[] = "(u.nome LIKE '%$b%' OR u.email LIKE '%$b%')";
}
$wh = implode(' AND ', $where);

$usuarios = mysqli_query($con,
    "SELECT u.*, p.nome AS perfil_nome FROM usuario u
     LEFT JOIN perfil_usuario p ON u.perfil_cod = p.cod
     WHERE $wh ORDER BY u.nome");
$total = (int)(mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) AS t FROM usuario WHERE $wh"))['t'] ?? 0);

$msgs = [
    'criado'   => ['sucesso','Utilizador criado com sucesso!'],
    'editado'  => ['sucesso','Utilizador actualizado!'],
    'excluido' => ['aviso',  'Utilizador removido.'],
    'senha'    => ['sucesso','Palavra-passe alterada com sucesso!'],
];
[$mt, $mm] = $msgs[$_GET['msg'] ?? ''] ?? ['',''];

require_once("header.php");
?>

<main class="pagina">
  <div class="page-header">
    <div class="page-header-txt">
      <h1 class="pagina-titulo">Utilizadores do Sistema</h1>
      <p class="pagina-subtitulo">Gestão de acessos e permissões</p>
    </div>
    <div class="page-header-acoes">
      <a href="cadastro.php" class="btn btn-primario">
        <i class="material-icons-round">person_add</i> Novo utilizador
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
                 placeholder="Nome ou e-mail…" value="<?= htmlspecialchars($busca) ?>">
        </div>
      </div>
      <div class="form-grupo" style="align-self:flex-end;">
        <button type="submit" class="btn btn-primario"><i class="material-icons-round">search</i> Pesquisar</button>
      </div>
      <?php if ($busca): ?>
      <div class="form-grupo" style="align-self:flex-end;">
        <a href="lista_usuarios.php" class="btn btn-outline"><i class="material-icons-round">close</i> Limpar</a>
      </div>
      <?php endif; ?>
    </form>

    <div class="card-header" style="border-top:1px solid var(--c200);">
      <span class="card-titulo"><?= number_format($total) ?> utilizador(es)</span>
    </div>

    <div class="tabela-wrap">
      <table class="tabela">
        <thead><tr><th>Utilizador</th><th>E-mail</th><th>Perfil</th><th style="text-align:center;">Acções</th></tr></thead>
        <tbody>
        <?php if (mysqli_num_rows($usuarios) === 0): ?>
          <tr><td colspan="4">
            <div class="vazio">
              <i class="material-icons-round">people</i>
              <h3>Nenhum utilizador encontrado</h3>
              <a href="cadastro.php" class="btn btn-primario btn-sm"><i class="material-icons-round">person_add</i> Criar</a>
            </div>
          </td></tr>
        <?php else: ?>
          <?php while ($u = mysqli_fetch_assoc($usuarios)):
            $p  = explode(' ', trim($u['nome']));
            $av = strtoupper(substr($p[0],0,1)).(count($p)>1?strtoupper(substr(end($p),0,1)):'');
            $is_admin = (int)$u['perfil_cod'] === 1;
            $is_me    = (int)$u['cod'] === (int)$_SESSION['cod'];
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:50%;
                            background:<?= $is_admin?'var(--azul)':'var(--c200)' ?>;
                            display:flex;align-items:center;justify-content:center;
                            font-size:12px;font-weight:700;
                            color:<?= $is_admin?'#fff':'var(--c600)' ?>;flex-shrink:0;"><?= $av ?></div>
                <div>
                  <div class="td-nome">
                    <?= htmlspecialchars($u['nome']) ?>
                    <?php if ($is_me): ?>
                    <span class="badge badge-azul" style="font-size:10px;margin-left:4px;">Você</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </td>
            <td style="font-size:13px;color:var(--c600);"><?= htmlspecialchars($u['email']) ?></td>
            <td>
              <span class="badge <?= $is_admin?'badge-azul':'badge-cinza' ?>">
                <i class="material-icons-round" style="font-size:12px;"><?= $is_admin?'admin_panel_settings':'person' ?></i>
                <?= $is_admin?'Administrador':'Utilizador' ?>
              </span>
            </td>
            <td>
              <div class="acoes" style="justify-content:center;">
                <a href="editar_usuario.php?id=<?= $u['cod'] ?>" class="btn-icone" title="Editar">
                  <i class="material-icons-round" style="font-size:14px;">edit</i>
                </a>
                <a href="trocar_senha.php?id=<?= $u['cod'] ?>" class="btn-icone" title="Alterar palavra-passe" style="color:var(--laranja);">
                  <i class="material-icons-round" style="font-size:14px;">lock_reset</i>
                </a>
                <?php if (!$is_me): ?>
                <a href="excluir_usuario.php?id=<?= $u['cod'] ?>"
                   class="btn-icone perigo" title="Eliminar"
                   onclick="return confirm('Eliminar o utilizador <?= addslashes(htmlspecialchars($u['nome'])) ?>?')">
                  <i class="material-icons-round" style="font-size:14px;">delete</i>
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php require_once("footer.php"); ?>
