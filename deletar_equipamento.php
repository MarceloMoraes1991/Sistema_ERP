<?php
require_once("db/conexao.php");
require_once("bloqueio.php");
$cod = (int)($_GET['cod'] ?? $_GET['id'] ?? 0);
if ($cod === 0) { header("Location: listar_equipamento.php"); exit; }
mysqli_query($con, "DELETE FROM controle_equipamentos WHERE cod=$cod");
header("Location: listar_equipamento.php?msg=excluido"); exit;
