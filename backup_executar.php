<?php
require_once("db/conexao.php");
if ((int)$_SESSION['perfil'] !== 1) { header("Location: dashboard.php"); exit; }

$config_cod = (int)($_GET['config'] ?? 0);
$uc         = (int)$_SESSION['cod'];

// ── Dados de ligação ao MySQL (lê do conexao.php) ─────────
// Tenta ler as variáveis do ficheiro de ligação
$db_host = $con->host_info ? explode(' ', $con->host_info)[0] : 'localhost';
// Lê credenciais directamente
$db_conf_file = file_get_contents(__DIR__ . '/db/conexao.php');
preg_match('/mysqli_connect\(["\']([^"\']+)["\']\s*,\s*["\']([^"\']+)["\']\s*,\s*["\']([^"\']*)["\']/', $db_conf_file, $matches);
$db_host = $matches[1] ?? 'localhost';
$db_user = $matches[2] ?? 'root';
$db_pass = $matches[3] ?? '';
preg_match('/mysqli_select_db\([^,]+,\s*["\']([^"\']+)["\']/', $db_conf_file, $mdb);
if (!isset($mdb[1])) preg_match('/connect\([^,]+,[^,]+,[^,]+,\s*["\']([^"\']+)["\']/', $db_conf_file, $mdb);
$db_name = $mdb[1] ?? 'tarefasdiarias';

// ── Cria o ficheiro de backup ─────────────────────────────
$pasta_temp = __DIR__ . '/backup_files/';
if (!is_dir($pasta_temp)) mkdir($pasta_temp, 0755, true);

$nome_arquivo = 'backup_' . $db_name . '_' . date('Y-m-d_H-i-s') . '.sql';
$caminho_local = $pasta_temp . $nome_arquivo;

// Gera o backup via mysqldump
$cmd_dump = "mysqldump -h " . escapeshellarg($db_host)
           . " -u " . escapeshellarg($db_user)
           . (!empty($db_pass) ? " -p" . escapeshellarg($db_pass) : '')
           . " " . escapeshellarg($db_name)
           . " > " . escapeshellarg($caminho_local)
           . " 2>&1";

exec($cmd_dump, $output_dump, $ret_dump);

$sucesso = ($ret_dump === 0 && file_exists($caminho_local) && filesize($caminho_local) > 0);
$tamanho = $sucesso ? filesize($caminho_local) : 0;
$msg_erro = '';

if (!$sucesso) {
    // Fallback: PHP puro se mysqldump não estiver disponível
    $sql_backup = "-- ITM Technology — Backup de {$db_name}\n";
    $sql_backup .= "-- Gerado em: " . date('Y-m-d H:i:s') . "\n";
    $sql_backup .= "-- ============================================\n\n";
    $sql_backup .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tabelas = mysqli_query($con, "SHOW TABLES");
    while ($tab = mysqli_fetch_row($tabelas)) {
        $tabela = $tab[0];

        // Estrutura
        $create = mysqli_fetch_assoc(mysqli_query($con, "SHOW CREATE TABLE `$tabela`"));
        $sql_backup .= "DROP TABLE IF EXISTS `$tabela`;\n";
        $sql_backup .= $create['Create Table'] . ";\n\n";

        // Dados
        $rows = mysqli_query($con, "SELECT * FROM `$tabela`");
        if (mysqli_num_rows($rows) > 0) {
            while ($row = mysqli_fetch_assoc($rows)) {
                $vals = array_map(function($v) use ($con) {
                    return is_null($v) ? 'NULL' : "'" . mysqli_real_escape_string($con, $v) . "'";
                }, array_values($row));
                $cols = '`' . implode('`,`', array_keys($row)) . '`';
                $sql_backup .= "INSERT INTO `$tabela` ($cols) VALUES (" . implode(',', $vals) . ");\n";
            }
            $sql_backup .= "\n";
        }
    }
    $sql_backup .= "SET FOREIGN_KEY_CHECKS=1;\n";

    file_put_contents($caminho_local, $sql_backup);
    $sucesso = file_exists($caminho_local) && filesize($caminho_local) > 0;
    $tamanho = $sucesso ? filesize($caminho_local) : 0;
}

// ── Envia para o destino configurado ─────────────────────
$destino_nome = 'Local';
$destino_tipo = 'local';
$msg_upload   = '';
$upload_ok    = false;

if ($config_cod > 0) {
    $r = mysqli_query($con, "SELECT * FROM backup_configs WHERE cod=$config_cod AND ativo=1");
    if ($r && mysqli_num_rows($r) > 0) {
        $cfg  = mysqli_fetch_assoc($r);
        $conf = json_decode($cfg['config_json'], true) ?? [];
        $destino_nome = $cfg['nome'];
        $destino_tipo = $cfg['tipo'];

        switch ($cfg['tipo']) {

            // ── FTP ──────────────────────────────────────
            case 'ftp':
                $ftp = @ftp_connect($conf['host'], $conf['port'] ?? 21, 15);
                if ($ftp && @ftp_login($ftp, $conf['user'], $conf['pass'])) {
                    if ($conf['passive'] ?? false) ftp_pasv($ftp, true);
                    $dir_remoto = rtrim($conf['dir'] ?? '/', '/') . '/';
                    // Cria directório se não existir
                    @ftp_mkdir($ftp, $dir_remoto);
                    if (@ftp_put($ftp, $dir_remoto . $nome_arquivo, $caminho_local, FTP_BINARY)) {
                        $upload_ok = true;
                        $msg_upload = "Enviado para FTP: {$conf['host']}{$dir_remoto}{$nome_arquivo}";
                    } else {
                        $msg_erro = "Erro ao enviar ficheiro para o FTP.";
                    }
                    ftp_close($ftp);
                } else {
                    $msg_erro = "Não foi possível ligar ao servidor FTP: {$conf['host']}";
                }
                break;

            // ── SFTP ─────────────────────────────────────
            case 'sftp':
                if (!function_exists('ssh2_connect')) {
                    $msg_erro = "Extensão PHP SSH2 não instalada no servidor. Instale: pecl install ssh2";
                } else {
                    $ssh = @ssh2_connect($conf['host'], $conf['port'] ?? 22);
                    if ($ssh && @ssh2_auth_password($ssh, $conf['user'], $conf['pass'])) {
                        $sftp = ssh2_sftp($ssh);
                        $dir_remoto = rtrim($conf['dir'] ?? '/', '/') . '/';
                        @ssh2_sftp_mkdir($sftp, $dir_remoto, 0755, true);
                        if (@ssh2_scp_send($ssh, $caminho_local, $dir_remoto . $nome_arquivo, 0644)) {
                            $upload_ok = true;
                            $msg_upload = "Enviado via SFTP: {$conf['host']}{$dir_remoto}{$nome_arquivo}";
                        } else {
                            $msg_erro = "Erro ao enviar ficheiro via SFTP.";
                        }
                    } else {
                        $msg_erro = "Falha na autenticação SFTP em {$conf['host']}";
                    }
                }
                break;

            // ── GOOGLE DRIVE ──────────────────────────────
            case 'google_drive':
                // Usa a API REST do Google Drive com OAuth2
                $token_url = 'https://oauth2.googleapis.com/token';
                $token_data = http_build_query([
                    'client_id'     => $conf['client_id'],
                    'client_secret' => $conf['client_secret'],
                    'refresh_token' => $conf['refresh_token'],
                    'grant_type'    => 'refresh_token',
                ]);
                $ctx = stream_context_create(['http'=>['method'=>'POST','header'=>'Content-Type: application/x-www-form-urlencoded','content'=>$token_data]]);
                $token_resp = @file_get_contents($token_url, false, $ctx);
                $token_json = $token_resp ? json_decode($token_resp, true) : [];
                $access_token = $token_json['access_token'] ?? '';

                if ($access_token) {
                    $folder_id = $conf['folder_id'] ?? '';
                    $metadata  = json_encode(['name'=>$nome_arquivo,'parents'=>$folder_id?[$folder_id]:[]]);
                    $boundary  = '----FormBoundary' . md5(time());
                    $body      = "--$boundary\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n$metadata\r\n"
                               . "--$boundary\r\nContent-Type: application/sql\r\n\r\n" . file_get_contents($caminho_local) . "\r\n--$boundary--";
                    $up_ctx = stream_context_create(['http'=>[
                        'method'  => 'POST',
                        'header'  => "Authorization: Bearer $access_token\r\nContent-Type: multipart/related; boundary=$boundary",
                        'content' => $body,
                    ]]);
                    $up_resp = @file_get_contents('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart', false, $up_ctx);
                    $up_json = $up_resp ? json_decode($up_resp, true) : [];
                    if (isset($up_json['id'])) {
                        $upload_ok  = true;
                        $msg_upload = "Enviado para Google Drive: {$nome_arquivo} (ID: {$up_json['id']})";
                    } else {
                        $msg_erro = "Erro ao enviar para Google Drive: " . ($up_json['error']['message'] ?? 'Resposta inválida');
                    }
                } else {
                    $msg_erro = "Não foi possível obter token do Google. Verifique as credenciais OAuth2.";
                }
                break;

            // ── MEGA ─────────────────────────────────────
            case 'mega':
                // Tenta via MegaCMD se disponível
                $megacmd = shell_exec('which megacmd 2>/dev/null || which mega-login 2>/dev/null') ?? '';
                if (trim($megacmd) !== '') {
                    $dir_mega = rtrim($conf['dir'] ?? '/Backups/', '/') . '/';
                    exec("mega-login " . escapeshellarg($conf['email']) . " " . escapeshellarg($conf['pass']), $o, $r);
                    if ($r === 0) {
                        exec("mega-mkdir -p " . escapeshellarg($dir_mega), $o2, $r2);
                        exec("mega-put " . escapeshellarg($caminho_local) . " " . escapeshellarg($dir_mega), $o3, $r3);
                        if ($r3 === 0) {
                            $upload_ok  = true;
                            $msg_upload = "Enviado para MEGA: {$dir_mega}{$nome_arquivo}";
                        } else {
                            $msg_erro = "Erro ao enviar para MEGA via MegaCMD.";
                        }
                        exec("mega-logout");
                    } else {
                        $msg_erro = "Falha no login MEGA. Verifique as credenciais.";
                    }
                } else {
                    // Fallback: aviso que MegaCMD não está instalado
                    $msg_erro = "MegaCMD não encontrado no servidor. Instale em: https://mega.nz/cmd\nO ficheiro foi guardado localmente em: $caminho_local";
                    // Guarda localmente como fallback
                    $upload_ok = true;
                    $msg_upload = "MegaCMD não disponível — backup guardado localmente: $nome_arquivo";
                }
                break;

            // ── AMAZON S3 ─────────────────────────────────
            case 's3':
                // S3 via API REST assinada (AWS Signature V4)
                $bucket   = $conf['bucket']  ?? '';
                $region   = $conf['region']  ?? 'eu-west-1';
                $key_id   = $conf['key']     ?? '';
                $key_sec  = $conf['secret']  ?? '';
                $prefix   = rtrim($conf['prefix'] ?? 'backups/', '/') . '/';
                $s3_key   = $prefix . $nome_arquivo;
                $endpoint = "https://{$bucket}.s3.{$region}.amazonaws.com/{$s3_key}";

                $content  = file_get_contents($caminho_local);
                $content_md5 = base64_encode(md5($content, true));
                $date_iso = gmdate('Ymd\THis\Z');
                $date_s   = gmdate('Ymd');
                $payload_hash = hash('sha256', $content);

                $headers_sign = "content-md5\ncontent-type\nhost\nx-amz-content-sha256\nx-amz-date";
                $canonical = "PUT\n/{$s3_key}\n\ncontent-md5:{$content_md5}\ncontent-type:application/sql\nhost:{$bucket}.s3.{$region}.amazonaws.com\nx-amz-content-sha256:{$payload_hash}\nx-amz-date:{$date_iso}\n\n{$headers_sign}\n{$payload_hash}";
                $string_to_sign = "AWS4-HMAC-SHA256\n{$date_iso}\n{$date_s}/{$region}/s3/aws4_request\n" . hash('sha256', $canonical);

                $signing_key = hash_hmac('sha256', 'aws4_request',
                    hash_hmac('sha256', 's3',
                        hash_hmac('sha256', $region,
                            hash_hmac('sha256', $date_s, 'AWS4'.$key_sec, true), true), true), true);
                $signature = hash_hmac('sha256', $string_to_sign, $signing_key);
                $auth_header = "AWS4-HMAC-SHA256 Credential={$key_id}/{$date_s}/{$region}/s3/aws4_request,SignedHeaders={$headers_sign},Signature={$signature}";

                $s3_ctx = stream_context_create(['http'=>[
                    'method'  => 'PUT',
                    'header'  => implode("\r\n",[
                        "Authorization: $auth_header",
                        "Content-MD5: $content_md5",
                        "Content-Type: application/sql",
                        "x-amz-content-sha256: $payload_hash",
                        "x-amz-date: $date_iso",
                    ]),
                    'content' => $content,
                    'ignore_errors' => true,
                ]]);
                @file_get_contents($endpoint, false, $s3_ctx);
                $resp_code = 0;
                foreach ($http_response_header ?? [] as $h) {
                    if (preg_match('/HTTP\/\d\.\d (\d+)/', $h, $m)) $resp_code = (int)$m[1];
                }
                if ($resp_code >= 200 && $resp_code < 300) {
                    $upload_ok  = true;
                    $msg_upload = "Enviado para S3: s3://{$bucket}/{$s3_key}";
                } else {
                    $msg_erro = "Erro ao enviar para S3. Código HTTP: {$resp_code}. Verifique as credenciais e permissões.";
                }
                break;

            // ── LOCAL ─────────────────────────────────────
            case 'local':
            default:
                $upload_ok  = true;
                $msg_upload = "Guardado localmente em: backup_files/{$nome_arquivo}";

                // Limpa ficheiros antigos
                $max = (int)($conf['max_files'] ?? 10);
                $arquivos = glob($pasta_temp . 'backup_*.sql');
                if ($arquivos && count($arquivos) > $max) {
                    usort($arquivos, fn($a,$b) => filemtime($a)-filemtime($b));
                    $a_eliminar = array_slice($arquivos, 0, count($arquivos)-$max);
                    foreach ($a_eliminar as $arq) @unlink($arq);
                }
                break;
        }
    }
} else {
    // Sem configuração → guarda local
    $upload_ok  = $sucesso;
    $msg_upload = "Guardado localmente: backup_files/{$nome_arquivo}";
    $destino_tipo = 'local';
}

// ── Regista no histórico ──────────────────────────────────
$status_reg = ($sucesso && $upload_ok) ? 'sucesso' : 'erro';
$msg_reg    = mysqli_real_escape_string($con, $upload_ok ? $msg_upload : ($msg_erro ?: 'Erro desconhecido'));
$arq_esc    = mysqli_real_escape_string($con, $nome_arquivo);
$dest_esc   = mysqli_real_escape_string($con, $destino_nome);
$tipo_esc   = mysqli_real_escape_string($con, $destino_tipo);
$cfg_ref    = $config_cod > 0 ? $config_cod : "NULL";

mysqli_query($con, "INSERT INTO backup_historico
    (config_cod,destino,tipo,arquivo,tamanho,status,mensagem,usuario_cod,concluido_em)
    VALUES ($cfg_ref,'$dest_esc','$tipo_esc','$arq_esc',$tamanho,'$status_reg','$msg_reg',$uc,NOW())");

// Se o backup foi local e deu erro no upload, remove o ficheiro temporário
if ($sucesso && !$upload_ok && $destino_tipo !== 'local') {
    // Mantém o ficheiro local como fallback
}

// ── Redireciona ───────────────────────────────────────────
$msg_redir = ($sucesso && $upload_ok) ? 'ok' : 'erro';
header("Location: backup.php?msg=$msg_redir");
exit;
