<?php
require_once("db/conexao.php");
require_once("bloqueio.php");

$cod       = (int)($_POST['cod']       ?? 0);
$estado    = (int)($_POST['estado_cod']?? 0);

if ($cod === 0 || $estado === 0) { header("Location: orcamentos.php"); exit; }

// Verifica se orçamento existe
$r = mysqli_query($con, "SELECT cod FROM orcamentos WHERE cod=$cod");
if (!$r || mysqli_num_rows($r) === 0) { header("Location: orcamentos.php"); exit; }

mysqli_query($con, "UPDATE orcamentos SET estado_cod=$estado WHERE cod=$cod");

header("Location: orcamento_ver.php?cod=$cod&msg=editado");
exit;
