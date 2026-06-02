<?php
require_once("db/conexao.php");
require_once("bloqueio.php");

$cod = (int)($_GET['cod'] ?? 0);
if ($cod === 0) { header("Location: orcamentos.php"); exit; }

$r = mysqli_query($con, "SELECT cod FROM orcamentos WHERE cod=$cod");
if (!$r || mysqli_num_rows($r) === 0) { header("Location: orcamentos.php"); exit; }

// As linhas apagam em cascata (ON DELETE CASCADE)
mysqli_query($con, "DELETE FROM orcamentos WHERE cod=$cod");

header("Location: orcamentos.php?msg=excluido");
exit;
