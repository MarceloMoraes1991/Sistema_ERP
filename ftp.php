<?php
require_once("db/conexao.php");
if ((int)$_SESSION['perfil'] !== 1) { header("Location: dashboard.php"); exit; }

// Carrega configs FTP/SFTP
$configs_ftp = mysqli_query($con,
    "SELECT * FROM backup_configs WHERE tipo IN ('ftp','sftp') AND ativo=1 ORDER BY nome");

$sel_cfg = (int)($_GET['cfg'] ?? 0);
$caminho = trim($_GET['path'] ?? '/');
$msg     = $_GET['msg'] ?? '';

$arquivos_remotos = [];
$cfg_ativa        = null;
$erro_ftp         = '';
$ftp_conn         = null;

// Liga ao FTP seleccionado
if ($sel_cfg > 0) {
    $r = mysqli_query($con, "SELECT * FROM backup_configs WHERE cod=$sel_cfg");
    if ($r && mysqli_num_rows($r) > 0) {
        $cfg_ativa = mysqli_fetch_assoc($r);
        $conf      = json_decode($cfg_ativa['config_json'], true) ?? [];

        if ($cfg_ativa['tipo'] === 'ftp') {
            $ftp_conn = @ftp_connect($conf['host'], $conf['port']??21, 10);
            if ($ftp_conn && @ftp_login($ftp_conn, $conf['user'], $conf['pass'])) {
                if ($conf['passive']??false) ftp_pasv($ftp_conn, true);
                $lista = @ftp_nlist($ftp_conn, $caminho);
                if ($lista !== false) {
                    foreach ($lista as $arq) {
                        $nome = basename($arq);
                        if ($nome === '.' || $nome === '..') continue;
                        $tamanho = @ftp_size($ftp_conn, $arq);
                        $arquivos_remotos[] = [
                            'nome'    => $nome,
                            'caminho' => $arq,
                            'tamanho' => $tamanho,
                            'dir'     => $tamanho === -1,
                        ];
                    }
                }
            } else {
                $erro_ftp = "Não foi possível ligar ao servidor FTP: {$conf['host']}";
            }
        }
    }
}

// Upload de ficheiro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ficheiro']) && $ftp_conn) {
    $conf = json_decode($cfg_ativa['config_json'], true) ?? [];
    if ($_FILES['ficheiro']['error'] === UPLOAD_ERR_OK) {
        $nome_remoto = rtrim($caminho, '/') . '/' . basename($_FILES['ficheiro']['name']);
        if (@ftp_put($ftp_conn, $nome_remoto, $_FILES['ficheiro']['tmp_name'], FTP_BINARY)) {
            header("Location: ftp.php?cfg=$sel_cfg&path=" . urlencode($caminho) . "&msg=up_ok");
        } else {
            $erro_ftp = "Erro ao enviar o ficheiro.";
        }
        exit;
    }
}

require_once("header.php");

// Formata tamanho
function fmt_size($bytes) {
    if ($bytes < 0) return '—';
    if ($bytes >= 1048576) return round($bytes/1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes/1024, 1)    . ' KB';
    return $bytes . ' B';
}
?>

<main class="pagina">

  <div class="page-header">
    <div class="page-header-txt">
      <h1 class="pagina-titulo">Gestor FTP</h1>
      <p class="pagina-subtitulo">Navegue e faça upload de ficheiros nos servidores FTP configurados</p>
    </div>
    <div class="page-header-acoes">
      <a href="backup_config_form.php?tipo=ftp" class="btn btn-outline">
        <i class="material-icons-round">add</i> Adicionar servidor FTP
      </a>
    </div>
  </div>

  <?php if ($msg === 'up_ok'): ?>
  <div class="alerta alerta-sucesso mb-20">
    <i class="material-icons-round">check_circle</i>
    <span>Ficheiro enviado com sucesso!</span>
    <button data-fechar-alerta style="margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;">
      <i class="material-icons-round" style="font-size:18px;">close</i>
    </button>
  </div>
  <?php endif; ?>

  <div class="grid-2 mb-16" style="grid-template-columns:280px 1fr;">

    <!-- Painel lateral: servidores -->
    <div class="card" style="height:fit-content;">
      <div class="card-header"><span class="card-titulo">Servidores FTP</span></div>
      <?php if (mysqli_num_rows($configs_ftp) === 0): ?>
      <div class="vazio" style="padding:24px;">
        <i class="material-icons-round">cloud_off</i>
        <h3>Sem servidores FTP</h3>
        <a href="backup_config_form.php" class="btn btn-primario btn-sm"><i class="material-icons-round">add</i> Adicionar</a>
      </div>
      <?php else: ?>
      <div>
        <?php while ($cfg = mysqli_fetch_assoc($configs_ftp)):
          $conf_tmp = json_decode($cfg['config_json'], true) ?? [];
          $ativo    = $sel_cfg === (int)$cfg['cod'];
        ?>
        <a href="ftp.php?cfg=<?= $cfg['cod'] ?>&path=/"
           style="display:flex;align-items:center;gap:10px;padding:12px 16px;
                  border-bottom:1px solid var(--c100);
                  background:<?= $ativo?'var(--azul-claro)':'' ?>;
                  color:<?= $ativo?'var(--azul)':'var(--c700)' ?>;
                  text-decoration:none;transition:background .15s;">
          <i class="material-icons-round" style="font-size:20px;color:<?= $ativo?'var(--azul)':'var(--c400)' ?>;">
            <?= $cfg['tipo']==='sftp'?'lock':'cloud_upload' ?>
          </i>
          <div style="flex:1;min-width:0;">
            <div style="font-weight:<?= $ativo?'600':'400' ?>;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
              <?= htmlspecialchars($cfg['nome']) ?>
            </div>
            <div style="font-size:11px;color:var(--c500);"><?= htmlspecialchars($conf_tmp['host']??'—') ?></div>
          </div>
          <?php if ($ativo): ?><i class="material-icons-round" style="font-size:16px;flex-shrink:0;">chevron_right</i><?php endif; ?>
        </a>
        <?php endwhile; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Painel principal: browser de ficheiros -->
    <div>
      <?php if (!$sel_cfg): ?>
      <div class="card">
        <div class="vazio" style="padding:52px;">
          <i class="material-icons-round">cloud_upload</i>
          <h3>Seleccione um servidor</h3>
          <p>Escolha um servidor FTP na lista à esquerda para navegar nos ficheiros.</p>
        </div>
      </div>

      <?php elseif ($erro_ftp): ?>
      <div class="alerta alerta-erro">
        <i class="material-icons-round">error_outline</i>
        <span><?= htmlspecialchars($erro_ftp) ?></span>
      </div>

      <?php else: ?>
      <!-- Breadcrumb do caminho -->
      <div class="card mb-16">
        <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:12px 16px;">
          <div style="display:flex;align-items:center;gap:4px;font-size:13px;font-family:monospace;flex-wrap:wrap;">
            <?php
            $partes = array_filter(explode('/', $caminho));
            echo '<a href="ftp.php?cfg='.$sel_cfg.'&path=/" style="color:var(--azul);font-weight:600;">🏠 /</a>';
            $acum = '';
            foreach ($partes as $parte) {
                $acum .= '/' . $parte;
                echo ' <span style="color:var(--c400);">/</span> ';
                echo '<a href="ftp.php?cfg='.$sel_cfg.'&path='.urlencode($acum).'" style="color:var(--azul);">'.htmlspecialchars($parte).'</a>';
            }
            ?>
          </div>
          <!-- Upload -->
          <form method="POST" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;">
            <input type="file" name="ficheiro" id="ftp-upload" style="display:none;" onchange="this.form.submit()">
            <label for="ftp-upload" class="btn btn-primario btn-sm" style="cursor:pointer;">
              <i class="material-icons-round">upload</i> Enviar ficheiro
            </label>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <span class="card-titulo"><?= count($arquivos_remotos) ?> item(ns)</span>
        </div>

        <?php if (empty($arquivos_remotos)): ?>
        <div class="vazio" style="padding:32px;">
          <i class="material-icons-round">folder_open</i>
          <h3>Pasta vazia</h3>
        </div>
        <?php else: ?>
        <div class="tabela-wrap">
          <table class="tabela">
            <thead>
              <tr><th>Nome</th><th>Tipo</th><th>Tamanho</th></tr>
            </thead>
            <tbody>
            <?php
            // Pastas primeiro
            usort($arquivos_remotos, fn($a,$b) => $b['dir']-$a['dir']);
            foreach ($arquivos_remotos as $arq):
            ?>
            <tr>
              <td>
                <?php if ($arq['dir']): ?>
                <a href="ftp.php?cfg=<?= $sel_cfg ?>&path=<?= urlencode($arq['caminho']) ?>"
                   style="display:flex;align-items:center;gap:8px;color:var(--azul);font-weight:500;">
                  <i class="material-icons-round" style="color:var(--amarelo);font-size:18px;">folder</i>
                  <?= htmlspecialchars($arq['nome']) ?>
                </a>
                <?php else: ?>
                <div style="display:flex;align-items:center;gap:8px;">
                  <i class="material-icons-round" style="color:var(--c400);font-size:18px;">insert_drive_file</i>
                  <span style="font-size:13px;"><?= htmlspecialchars($arq['nome']) ?></span>
                </div>
                <?php endif; ?>
              </td>
              <td style="font-size:12px;color:var(--c500);"><?= $arq['dir']?'Pasta':'Ficheiro' ?></td>
              <td style="font-size:12px;color:var(--c500);"><?= fmt_size($arq['tamanho']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

  </div>

</main>

<?php
if ($ftp_conn) ftp_close($ftp_conn);
require_once("footer.php");
?>
