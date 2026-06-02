<?php
// arquivar_chips.php
require_once("db/conexao.php");
require_once("bloqueio.php");

$cod  = (int)($_GET['cod']  ?? 0);
$acao = $_GET['acao'] ?? 'arquivar';
if ($cod === 0) { header("Location: chips.php"); exit; }

$novo_status = $acao === 'reativar' ? 'ativo' : 'arquivado';
mysqli_query($con, "UPDATE chips SET status='$novo_status' WHERE cod=$cod");

header("Location: chips.php?msg=" . ($acao === 'reativar' ? 'editado' : 'arquivado'));
exit;
