<?php
require_once("db/conexao.php");
if ((int)$_SESSION['perfil'] !== 1) { header("Location: dashboard.php"); exit; }

// Carrega configurações guardadas
$configs    = mysqli_query($con, "SELECT * FROM backup_configs ORDER BY nome");
$historico  = mysqli_query($con,
    "SELECT h.*, u.nome AS user_nome
     FROM backup_historico h
     LEFT JOIN usuario u ON h.usuario_cod = u.cod
     ORDER BY h.iniciado_em DESC LIMIT 20");

// Totais
$tot = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT
        SUM(status='sucesso') AS sucessos,
        SUM(status='erro')    AS erros,
        COUNT(*)              AS total
     FROM backup_historico")) ?? [];

$msgs = [
    'ok'     => ['sucesso','Backup realizado com sucesso!'],
    'config' => ['sucesso','Configuração guardada!'],
    'del'    => ['aviso',  'Configuração eliminada.'],
    'erro'   => ['erro',   'Ocorreu um erro. Verifique as configurações.'],
];
[$mt, $mm] = $msgs[$_GET['msg'] ?? ''] ?? ['',''];

require_once("header.php");
?>

<main class="pagina">

  <div class="page-header">
    <div class="page-header-txt">
      <h1 class="pagina-titulo">Cópia de Segurança</h1>
      <p class="pagina-subtitulo">Gerir backups da base de dados e ficheiros do sistema</p>
    </div>
    <div class="page-header-acoes">
      <a href="backup_config_form.php" class="btn btn-outline">
        <i class="material-icons-round">settings</i> Nova configuração
      </a>
      <a href="backup_executar.php" class="btn btn-primario"
         onclick="return confirm('Iniciar backup agora?')">
        <i class="material-icons-round">backup</i> Fazer backup agora
      </a>
    </div>
  </div>

  <?php if ($mm): ?>
  <div class="alerta alerta-<?= $mt ?> mb-20">
    <i class="material-icons-round"><?= $mt==='sucesso'?'check_circle':($mt==='aviso'?'warning':'error') ?></i>
    <span><?= htmlspecialchars($mm) ?></span>
    <button data-fechar-alerta style="margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;">
      <i class="material-icons-round" style="font-size:18px;">close</i>
    </button>
  </div>
  <?php endif; ?>

  <!-- Métricas -->
  <div class="metricas mb-20">
    <div class="metrica">
      <div class="metrica-acento acento-azul"></div>
      <div class="metrica-label"><i class="material-icons-round">backup</i> Total backups</div>
      <div class="metrica-valor"><?= $tot['total'] ?? 0 ?></div>
      <div class="metrica-sub">Backups realizados</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-verde"></div>
      <div class="metrica-label"><i class="material-icons-round">check_circle</i> Sucessos</div>
      <div class="metrica-valor"><?= $tot['sucessos'] ?? 0 ?></div>
      <div class="metrica-sub">Concluídos com êxito</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-vermelho"></div>
      <div class="metrica-label"><i class="material-icons-round">error</i> Erros</div>
      <div class="metrica-valor"><?= $tot['erros'] ?? 0 ?></div>
      <div class="metrica-sub">Falharam</div>
    </div>
    <div class="metrica">
      <div class="metrica-acento acento-laranja"></div>
      <div class="metrica-label"><i class="material-icons-round">settings</i> Destinos</div>
      <div class="metrica-valor"><?= mysqli_num_rows($configs) ?></div>
      <div class="metrica-sub">Configurados</div>
    </div>
  </div>

  <!-- Destinos configurados -->
  <div class="card mb-16">
    <div class="card-header">
      <span class="card-titulo">Destinos de backup configurados</span>
      <a href="backup_config_form.php" class="btn btn-outline btn-sm">
        <i class="material-icons-round">add</i> Adicionar destino
      </a>
    </div>

    <?php if (mysqli_num_rows($configs) === 0): ?>
    <div class="vazio">
      <i class="material-icons-round">cloud_off</i>
      <h3>Nenhum destino configurado</h3>
      <p>Configure pelo menos um destino para guardar os backups.</p>
      <a href="backup_config_form.php" class="btn btn-primario btn-sm">
        <i class="material-icons-round">add</i> Configurar destino
      </a>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;padding:18px;">
      <?php
      $icones = [
        'ftp'          => ['cloud_upload',   'var(--azul)',     'var(--azul-claro)',     'FTP'],
        'sftp'         => ['lock',           'var(--roxo)',     'var(--roxo-claro)',     'SFTP'],
        'google_drive' => ['add_to_drive',   'var(--verde)',    'var(--verde-claro)',    'Google Drive'],
        'mega'         => ['storage',        'var(--laranja)', 'var(--laranja-claro)',  'MEGA'],
        's3'           => ['cloud',          'var(--ciano)',    'var(--ciano-claro)',    'Amazon S3'],
        'local'        => ['save',           'var(--c600)',     'var(--c200)',           'Local'],
      ];
      while ($cfg = mysqli_fetch_assoc($configs)):
        [$ic,$cor,$bg,$label] = $icones[$cfg['tipo']] ?? ['cloud','var(--c600)','var(--c100)',$cfg['tipo']];
        $conf = json_decode($cfg['config_json'], true) ?? [];
      ?>
      <div style="border:1px solid var(--c200);border-radius:var(--r-md);padding:16px;background:#fff;
                  <?= !$cfg['ativo'] ? 'opacity:.55;' : '' ?>">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
          <div style="width:40px;height:40px;border-radius:var(--r-sm);background:<?= $bg ?>;
                      display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="material-icons-round" style="font-size:20px;color:<?= $cor ?>;"><?= $ic ?></i>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-weight:600;color:var(--c900);font-size:14px;"><?= htmlspecialchars($cfg['nome']) ?></div>
            <div style="font-size:12px;color:var(--c500);"><?= $label ?></div>
          </div>
          <span class="badge <?= $cfg['ativo']?'badge-verde':'badge-cinza' ?>" style="font-size:10px;">
            <?= $cfg['ativo']?'Activo':'Inactivo' ?>
          </span>
        </div>

        <!-- Info do destino -->
        <div style="font-size:12px;color:var(--c500);margin-bottom:12px;line-height:1.7;">
          <?php if ($cfg['tipo']==='ftp' || $cfg['tipo']==='sftp'): ?>
            <div>🌐 <?= htmlspecialchars($conf['host']??'—') ?></div>
            <div>👤 <?= htmlspecialchars($conf['user']??'—') ?></div>
            <div>📁 <?= htmlspecialchars($conf['dir']??'/') ?></div>
          <?php elseif ($cfg['tipo']==='google_drive'): ?>
            <div>📧 <?= htmlspecialchars($conf['email']??'—') ?></div>
            <div>📁 <?= htmlspecialchars($conf['folder']??'Raiz') ?></div>
          <?php elseif ($cfg['tipo']==='mega'): ?>
            <div>📧 <?= htmlspecialchars($conf['email']??'—') ?></div>
            <div>📁 <?= htmlspecialchars($conf['dir']??'/Backups') ?></div>
          <?php elseif ($cfg['tipo']==='s3'): ?>
            <div>🪣 <?= htmlspecialchars($conf['bucket']??'—') ?></div>
            <div>🌍 <?= htmlspecialchars($conf['region']??'—') ?></div>
          <?php elseif ($cfg['tipo']==='local'): ?>
            <div>📁 <?= htmlspecialchars($conf['path']??'backup_files/') ?></div>
          <?php endif; ?>
        </div>

        <div style="display:flex;gap:6px;">
          <a href="backup_executar.php?config=<?= $cfg['cod'] ?>"
             class="btn btn-primario btn-sm" style="flex:1;justify-content:center;"
             onclick="return confirm('Fazer backup para «<?= addslashes(htmlspecialchars($cfg['nome'])) ?>» agora?')">
            <i class="material-icons-round">backup</i> Backup
          </a>
          <a href="backup_config_form.php?cod=<?= $cfg['cod'] ?>" class="btn-icone" title="Editar">
            <i class="material-icons-round" style="font-size:14px;">edit</i>
          </a>
          <a href="backup_config_excluir.php?cod=<?= $cfg['cod'] ?>"
             class="btn-icone perigo" title="Eliminar"
             onclick="return confirm('Eliminar esta configuração?')">
            <i class="material-icons-round" style="font-size:14px;">delete</i>
          </a>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Histórico -->
  <div class="card">
    <div class="card-header">
      <span class="card-titulo">Histórico de backups</span>
      <a href="backup_historico_limpar.php"
         class="btn btn-outline btn-sm" style="color:var(--vermelho);"
         onclick="return confirm('Limpar todo o histórico?')">
        <i class="material-icons-round">delete_sweep</i> Limpar histórico
      </a>
    </div>

    <?php if (mysqli_num_rows($historico) === 0): ?>
    <div class="vazio" style="padding:32px;">
      <i class="material-icons-round">history</i>
      <h3>Sem histórico ainda</h3>
      <p>Os backups realizados aparecem aqui.</p>
    </div>
    <?php else: ?>
    <div class="tabela-wrap">
      <table class="tabela">
        <thead>
          <tr>
            <th>Data / Hora</th>
            <th>Destino</th>
            <th>Ficheiro</th>
            <th>Tamanho</th>
            <th>Estado</th>
            <th>Utilizador</th>
            <th>Mensagem</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($h = mysqli_fetch_assoc($historico)):
          [$badge,$blabel] = match($h['status']) {
            'sucesso'      => ['badge-verde',   'Sucesso'],
            'erro'         => ['badge-vermelho','Erro'],
            'a_processar'  => ['badge-azul',    'A processar'],
            default        => ['badge-cinza',   $h['status']],
          };
          // Formata tamanho
          $tam = '—';
          if ($h['tamanho']) {
            $bytes = (int)$h['tamanho'];
            if ($bytes >= 1048576)     $tam = round($bytes/1048576,1).' MB';
            elseif ($bytes >= 1024)    $tam = round($bytes/1024,1).' KB';
            else                       $tam = $bytes.' B';
          }
        ?>
        <tr>
          <td style="white-space:nowrap;font-size:12px;">
            <div style="font-weight:500;color:var(--c800);"><?= date('d/m/Y',strtotime($h['iniciado_em'])) ?></div>
            <div style="color:var(--c500);"><?= date('H:i:s',strtotime($h['iniciado_em'])) ?></div>
          </td>
          <td>
            <div style="font-size:13px;font-weight:500;color:var(--c800);"><?= htmlspecialchars($h['destino']) ?></div>
            <div style="font-size:11px;color:var(--c500);"><?= htmlspecialchars($h['tipo']) ?></div>
          </td>
          <td style="font-size:12px;font-family:monospace;color:var(--c600);">
            <?= htmlspecialchars(basename($h['arquivo'])) ?>
          </td>
          <td style="font-size:12px;color:var(--c500);"><?= $tam ?></td>
          <td><span class="badge <?= $badge ?>"><?= $blabel ?></span></td>
          <td style="font-size:12px;color:var(--c600);"><?= htmlspecialchars($h['user_nome']??'—') ?></td>
          <td style="font-size:12px;color:var(--c500);max-width:200px;">
            <?php if ($h['mensagem']): ?>
            <span title="<?= htmlspecialchars($h['mensagem']) ?>">
              <?= htmlspecialchars(mb_strimwidth($h['mensagem'],0,50,'…')) ?>
            </span>
            <?php else: ?>—<?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</main>

<?php require_once("footer.php"); ?>
