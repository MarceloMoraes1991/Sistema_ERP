<?php
require_once("db/conexao.php");
if ((int)$_SESSION['perfil'] !== 1) { header("Location: dashboard.php"); exit; }

$cod    = (int)($_GET['cod'] ?? 0);
$editar = $cod > 0;
$cfg    = [];
$erros  = [];
$conf   = [];

if ($editar) {
    $r = mysqli_query($con, "SELECT * FROM backup_configs WHERE cod=$cod");
    if (!$r || mysqli_num_rows($r) === 0) { header("Location: backup.php"); exit; }
    $cfg  = mysqli_fetch_assoc($r);
    $conf = json_decode($cfg['config_json'], true) ?? [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome']  ?? '');
    $tipo  = $_POST['tipo']       ?? '';
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if ($nome === '') $erros[] = 'O nome é obrigatório.';
    if (!in_array($tipo, ['ftp','sftp','google_drive','mega','s3','local'])) $erros[] = 'Seleccione um tipo válido.';

    // Recolhe configuração conforme tipo
    $conf_nova = [];
    switch ($tipo) {
        case 'ftp':
        case 'sftp':
            $conf_nova = [
                'host' => trim($_POST['ftp_host'] ?? ''),
                'port' => (int)($_POST['ftp_port'] ?? ($tipo==='sftp'?22:21)),
                'user' => trim($_POST['ftp_user'] ?? ''),
                'pass' => trim($_POST['ftp_pass'] ?? ''),
                'dir'  => trim($_POST['ftp_dir']  ?? '/backups/'),
                'passive' => isset($_POST['ftp_passive']) ? true : false,
            ];
            if (!$conf_nova['host']) $erros[] = 'O servidor FTP é obrigatório.';
            if (!$conf_nova['user']) $erros[] = 'O utilizador FTP é obrigatório.';
            break;

        case 'google_drive':
            $conf_nova = [
                'client_id'     => trim($_POST['gd_client_id']     ?? ''),
                'client_secret' => trim($_POST['gd_client_secret'] ?? ''),
                'refresh_token' => trim($_POST['gd_refresh_token'] ?? ''),
                'folder_id'     => trim($_POST['gd_folder_id']     ?? ''),
                'email'         => trim($_POST['gd_email']         ?? ''),
                'folder'        => trim($_POST['gd_folder']        ?? 'Backups ITM'),
            ];
            if (!$conf_nova['client_id']) $erros[] = 'O Client ID do Google é obrigatório.';
            break;

        case 'mega':
            $conf_nova = [
                'email' => trim($_POST['mega_email'] ?? ''),
                'pass'  => trim($_POST['mega_pass']  ?? ''),
                'dir'   => trim($_POST['mega_dir']   ?? '/Backups/'),
            ];
            if (!$conf_nova['email']) $erros[] = 'O e-mail MEGA é obrigatório.';
            break;

        case 's3':
            $conf_nova = [
                'key'    => trim($_POST['s3_key']    ?? ''),
                'secret' => trim($_POST['s3_secret'] ?? ''),
                'bucket' => trim($_POST['s3_bucket'] ?? ''),
                'region' => trim($_POST['s3_region'] ?? 'eu-west-1'),
                'prefix' => trim($_POST['s3_prefix'] ?? 'backups/'),
            ];
            if (!$conf_nova['bucket']) $erros[] = 'O bucket S3 é obrigatório.';
            break;

        case 'local':
            $conf_nova = [
                'path'      => trim($_POST['local_path']  ?? 'backup_files/'),
                'max_files' => (int)($_POST['local_max']  ?? 10),
            ];
            break;
    }

    if (empty($erros)) {
        $n   = mysqli_real_escape_string($con, $nome);
        $t   = mysqli_real_escape_string($con, $tipo);
        $j   = mysqli_real_escape_string($con, json_encode($conf_nova, JSON_UNESCAPED_UNICODE));

        if ($editar) {
            mysqli_query($con, "UPDATE backup_configs SET nome='$n', tipo='$t', ativo=$ativo, config_json='$j' WHERE cod=$cod");
        } else {
            mysqli_query($con, "INSERT INTO backup_configs (nome,tipo,ativo,config_json) VALUES ('$n','$t',$ativo,'$j')");
        }
        header("Location: backup.php?msg=config"); exit;
    }

    // Re-popula para mostrar erros
    $cfg  = ['nome'=>$nome,'tipo'=>$tipo,'ativo'=>$ativo];
    $conf = $conf_nova;
}

$cfg['tipo']  = $cfg['tipo']  ?? 'ftp';
$cfg['ativo'] = $cfg['ativo'] ?? 1;

require_once("header.php");
function v($a,$k,$d=''){ return htmlspecialchars($a[$k] ?? $d); }
function s($a,$k,$v){ return ($a[$k]??'')===$v?'selected':''; }
?>

<main class="pagina" style="max-width:740px;">

  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="backup.php" class="btn-icone"><i class="material-icons-round">arrow_back</i></a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo"><?= $editar?'Editar destino':'Novo destino de backup' ?></h1>
        <p class="pagina-subtitulo">Configure onde os backups serão guardados</p>
      </div>
    </div>
  </div>

  <?php if (!empty($erros)): ?>
  <div class="alerta alerta-erro mb-20">
    <i class="material-icons-round">error_outline</i>
    <div><strong>Corrija os erros:</strong>
      <ul style="margin:4px 0 0 16px;padding:0;">
        <?php foreach ($erros as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>

  <form method="POST" id="form-cfg">

    <!-- Dados gerais -->
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Dados gerais</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo col-span-full">
            <label class="form-label">Nome da configuração <span class="obg">*</span></label>
            <input type="text" name="nome" class="form-input"
                   placeholder="Ex: FTP Principal, Google Drive Backup, MEGA Diário"
                   value="<?= v($cfg,'nome') ?>" required>
          </div>
          <div class="form-grupo">
            <label class="form-label">Tipo de destino <span class="obg">*</span></label>
            <select name="tipo" id="sel-tipo" class="form-select" onchange="mostrarSecao()">
              <option value="ftp"          <?= s($cfg,'tipo','ftp')          ?>>📡 FTP</option>
              <option value="sftp"         <?= s($cfg,'tipo','sftp')         ?>>🔒 SFTP (seguro)</option>
              <option value="google_drive" <?= s($cfg,'tipo','google_drive') ?>>☁️ Google Drive</option>
              <option value="mega"         <?= s($cfg,'tipo','mega')         ?>>🗄️ MEGA</option>
              <option value="s3"           <?= s($cfg,'tipo','s3')           ?>>🪣 Amazon S3</option>
              <option value="local"        <?= s($cfg,'tipo','local')        ?>>💾 Local (servidor)</option>
            </select>
          </div>
          <div class="form-grupo" style="display:flex;align-items:center;gap:10px;padding-top:22px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:500;color:var(--c700);">
              <input type="checkbox" name="ativo" value="1" <?= ($cfg['ativo']??1)?'checked':'' ?>
                     style="width:16px;height:16px;cursor:pointer;">
              Destino activo
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- ── FTP / SFTP ─────────────────────────────────── -->
    <div class="card mb-16 secao" id="sec-ftp">
      <div class="card-header">
        <span class="card-titulo" id="ftp-titulo">Configuração FTP</span>
      </div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo col-span-full">
            <label class="form-label">Servidor <span class="obg">*</span></label>
            <input type="text" name="ftp_host" class="form-input"
                   placeholder="ftp.exemplo.com ou 192.168.0.1"
                   value="<?= v($conf,'host') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Porto</label>
            <input type="number" name="ftp_port" class="form-input"
                   value="<?= v($conf,'port','21') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Directório remoto</label>
            <input type="text" name="ftp_dir" class="form-input"
                   placeholder="/backups/itm/"
                   value="<?= v($conf,'dir','/backups/') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Utilizador <span class="obg">*</span></label>
            <input type="text" name="ftp_user" class="form-input"
                   placeholder="utilizador_ftp"
                   value="<?= v($conf,'user') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Palavra-passe</label>
            <input type="password" name="ftp_pass" class="form-input"
                   placeholder="••••••••"
                   value="<?= v($conf,'pass') ?>">
          </div>
          <div class="form-grupo col-span-full">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:500;color:var(--c700);">
              <input type="checkbox" name="ftp_passive" value="1" <?= ($conf['passive']??false)?'checked':'' ?> style="width:16px;height:16px;">
              Modo passivo (recomendado)
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- ── GOOGLE DRIVE ───────────────────────────────── -->
    <div class="card mb-16 secao" id="sec-google_drive" style="display:none;">
      <div class="card-header">
        <span class="card-titulo">Configuração Google Drive</span>
        <a href="https://console.cloud.google.com/" target="_blank" class="btn btn-outline btn-sm">
          <i class="material-icons-round">open_in_new</i> Google Console
        </a>
      </div>
      <div class="card-body">
        <div class="alerta alerta-info" style="margin-bottom:16px;">
          <i class="material-icons-round">info</i>
          <div>
            <strong>Como obter as credenciais:</strong>
            <ol style="margin:6px 0 0 16px;padding:0;font-size:12.5px;line-height:1.8;">
              <li>Aceda ao <a href="https://console.cloud.google.com/" target="_blank" style="color:var(--azul);">Google Cloud Console</a></li>
              <li>Crie um projecto e active a <strong>Google Drive API</strong></li>
              <li>Crie credenciais OAuth 2.0 → Aplicação Web</li>
              <li>Copie o Client ID e Client Secret abaixo</li>
              <li>Gere o Refresh Token via OAuth Playground</li>
            </ol>
          </div>
        </div>
        <div class="form-grid form-grid-2">
          <div class="form-grupo">
            <label class="form-label">E-mail Google</label>
            <input type="email" name="gd_email" class="form-input"
                   placeholder="conta@gmail.com"
                   value="<?= v($conf,'email') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Nome da pasta</label>
            <input type="text" name="gd_folder" class="form-input"
                   placeholder="Backups ITM"
                   value="<?= v($conf,'folder','Backups ITM') ?>">
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Client ID <span class="obg">*</span></label>
            <input type="text" name="gd_client_id" class="form-input"
                   placeholder="000000000000-xxxxxxxxxxxxxxxx.apps.googleusercontent.com"
                   value="<?= v($conf,'client_id') ?>">
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Client Secret</label>
            <input type="password" name="gd_client_secret" class="form-input"
                   placeholder="GOCSPX-xxxxxxxxxxxxxxxx"
                   value="<?= v($conf,'client_secret') ?>">
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Refresh Token</label>
            <input type="password" name="gd_refresh_token" class="form-input"
                   placeholder="1//xxxxxxxxxxxxxxxxxx"
                   value="<?= v($conf,'refresh_token') ?>">
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">ID da pasta destino (opcional)</label>
            <input type="text" name="gd_folder_id" class="form-input"
                   placeholder="ID da pasta no Google Drive (deixe vazio para raiz)"
                   value="<?= v($conf,'folder_id') ?>">
            <div class="form-hint">Encontre o ID na URL da pasta: drive.google.com/drive/folders/<strong>ID_AQUI</strong></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── MEGA ───────────────────────────────────────── -->
    <div class="card mb-16 secao" id="sec-mega" style="display:none;">
      <div class="card-header">
        <span class="card-titulo">Configuração MEGA</span>
        <a href="https://mega.nz" target="_blank" class="btn btn-outline btn-sm">
          <i class="material-icons-round">open_in_new</i> MEGA.nz
        </a>
      </div>
      <div class="card-body">
        <div class="alerta alerta-info" style="margin-bottom:16px;">
          <i class="material-icons-round">info</i>
          <div>
            O backup para MEGA utiliza a <strong>biblioteca MegaCMD</strong> ou a API PHP unofficial.
            Certifique-se que o servidor tem o <code style="background:var(--c100);padding:2px 6px;border-radius:4px;">megacmd</code> instalado ou use o método FTP do MEGA.
          </div>
        </div>
        <div class="form-grid form-grid-2">
          <div class="form-grupo col-span-full">
            <label class="form-label">E-mail MEGA <span class="obg">*</span></label>
            <input type="email" name="mega_email" class="form-input"
                   placeholder="conta@mega.nz"
                   value="<?= v($conf,'email') ?>">
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Palavra-passe MEGA</label>
            <input type="password" name="mega_pass" class="form-input"
                   placeholder="Palavra-passe da conta MEGA"
                   value="<?= v($conf,'pass') ?>">
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Directório de destino</label>
            <input type="text" name="mega_dir" class="form-input"
                   placeholder="/Backups/ITM/"
                   value="<?= v($conf,'dir','/Backups/') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- ── AMAZON S3 ──────────────────────────────────── -->
    <div class="card mb-16 secao" id="sec-s3" style="display:none;">
      <div class="card-header">
        <span class="card-titulo">Configuração Amazon S3</span>
        <a href="https://aws.amazon.com/s3/" target="_blank" class="btn btn-outline btn-sm">
          <i class="material-icons-round">open_in_new</i> AWS Console
        </a>
      </div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo col-span-full">
            <label class="form-label">Bucket <span class="obg">*</span></label>
            <input type="text" name="s3_bucket" class="form-input"
                   placeholder="meu-bucket-backups"
                   value="<?= v($conf,'bucket') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Região</label>
            <select name="s3_region" class="form-select">
              <?php
              $regioes = ['eu-west-1'=>'EU (Irlanda)','eu-west-2'=>'EU (Londres)','eu-central-1'=>'EU (Frankfurt)','us-east-1'=>'US East (N. Virginia)','us-west-2'=>'US West (Oregon)','ap-southeast-1'=>'Asia (Singapura)','sa-east-1'=>'América do Sul (São Paulo)'];
              foreach ($regioes as $r => $n) echo "<option value='$r' ".($conf['region']??'eu-west-1'===$r?'selected':'').">$n</option>";
              ?>
            </select>
          </div>
          <div class="form-grupo">
            <label class="form-label">Prefixo / Pasta</label>
            <input type="text" name="s3_prefix" class="form-input"
                   placeholder="backups/itm/"
                   value="<?= v($conf,'prefix','backups/') ?>">
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Access Key ID <span class="obg">*</span></label>
            <input type="text" name="s3_key" class="form-input"
                   placeholder="AKIAIOSFODNN7EXAMPLE"
                   value="<?= v($conf,'key') ?>">
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Secret Access Key</label>
            <input type="password" name="s3_secret" class="form-input"
                   placeholder="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"
                   value="<?= v($conf,'secret') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- ── LOCAL ─────────────────────────────────────── -->
    <div class="card mb-16 secao" id="sec-local" style="display:none;">
      <div class="card-header"><span class="card-titulo">Configuração Local</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo col-span-full">
            <label class="form-label">Caminho da pasta</label>
            <input type="text" name="local_path" class="form-input"
                   placeholder="backup_files/ ou /var/backups/itm/"
                   value="<?= v($conf,'path','backup_files/') ?>">
            <div class="form-hint">Caminho relativo à raiz do projecto ou absoluto no servidor</div>
          </div>
          <div class="form-grupo">
            <label class="form-label">Máx. ficheiros a guardar</label>
            <input type="number" name="local_max" class="form-input"
                   min="1" max="999" placeholder="10"
                   value="<?= v($conf,'max_files','10') ?>">
            <div class="form-hint">Os mais antigos são eliminados automaticamente</div>
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;padding-bottom:24px;">
      <a href="backup.php" class="btn btn-outline">Cancelar</a>
      <button type="submit" class="btn btn-primario">
        <i class="material-icons-round"><?= $editar?'save':'add' ?></i>
        <?= $editar?'Guardar alterações':'Adicionar destino' ?>
      </button>
    </div>
  </form>
</main>

<script>
function mostrarSecao() {
  const tipo = document.getElementById('sel-tipo').value;
  document.querySelectorAll('.secao').forEach(s => s.style.display='none');

  // FTP e SFTP partilham a mesma secção
  const secId = (tipo==='sftp') ? 'sec-ftp' : 'sec-'+tipo;
  const sec = document.getElementById(secId);
  if (sec) sec.style.display='';

  // Muda título conforme SFTP ou FTP
  const titulo = document.getElementById('ftp-titulo');
  if (titulo) titulo.textContent = tipo==='sftp' ? 'Configuração SFTP (Seguro)' : 'Configuração FTP';

  // Muda porto padrão
  const porto = document.querySelector('[name="ftp_port"]');
  if (porto && !porto.value) porto.value = tipo==='sftp'?'22':'21';
}
mostrarSecao();
</script>

<?php require_once("footer.php"); ?>
