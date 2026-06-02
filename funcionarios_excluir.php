<?php
require_once("db/conexao.php");
require_once("bloqueio.php");
$cod = (int)($_GET['cod'] ?? 0);
if ($cod === 0) { header("Location: funcionarios.php"); exit; }
mysqli_query($con, "DELETE FROM funcionarios WHERE cod=$cod");
header("Location: funcionarios.php?msg=excluido");
exit;
