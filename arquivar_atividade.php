<?php
require_once("db/conexao.php");
require_once("bloqueio.php");

$cod  = (int)($_GET['cod']  ?? 0);
$acao = $_GET['acao'] ?? 'arquivar';
if ($cod === 0) { header("Location: visualizar_atividade.php"); exit; }

if ($acao === 'concluir') {
    mysqli_query($con, "UPDATE atividades SET status='concluida', data_fechamento=NOW() WHERE cod=$cod");
} elseif ($acao === 'reabrir') {
    mysqli_query($con, "UPDATE atividades SET status='aberta', data_fechamento=NULL WHERE cod=$cod");
} else {
    mysqli_query($con, "UPDATE atividades SET status='arquivada', data_fechamento=NOW() WHERE cod=$cod");
}

$dest = $acao === 'arquivar' ? 'visualizar_atividade.php?msg=arquivada' : 'visualizar_atividade.php?msg=editada';
header("Location: $dest");
exit;
