<?php
require_once("db/conexao.php");
if ((int)$_SESSION['perfil'] !== 1) { header("Location: dashboard.php"); exit; }

$arquivo = basename($_GET['arquivo'] ?? '');
if ($arquivo === '') { header("Location: backup.php"); exit; }

// Valida — apenas ficheiros .sql ou .zip dentro de backup_files/
if (!preg_match('/^backup_[\w\-]+\.(sql|gz|zip)$/', $arquivo)) {
    header("Location: backup.php?msg=erro"); exit;
}

$caminho = __DIR__ . '/backup_files/' . $arquivo;
if (!file_exists($caminho)) {
    header("Location: backup.php?msg=erro"); exit;
}

// Comprime em .gz se for .sql e não existir versão comprimida
if (substr($arquivo, -4) === '.sql') {
    $gz_path    = $caminho . '.gz';
    $gz_arquivo = $arquivo . '.gz';
    if (!file_exists($gz_path)) {
        $fp_in  = fopen($caminho, 'rb');
        $fp_out = gzopen($gz_path, 'wb9');
        while (!feof($fp_in)) gzwrite($fp_out, fread($fp_in, 65536));
        fclose($fp_in);
        gzclose($fp_out);
    }
    // Serve o .gz
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . $gz_arquivo . '"');
    header('Content-Length: ' . filesize($gz_path));
    readfile($gz_path);
    exit;
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $arquivo . '"');
header('Content-Length: ' . filesize($caminho));
readfile($caminho);
exit;
