<?php
require_once("db/conexao.php");
require_once("bloqueio.php");
$cod = (int)($_GET['cod'] ?? 0);
if ($cod === 0) { header("Location: estoque.php"); exit; }
mysqli_query($con, "DELETE FROM estoque_movimentacoes WHERE estoque_cod=$cod");
mysqli_query($con, "DELETE FROM estoque WHERE cod=$cod");
header("Location: estoque.php?msg=excluido"); exit;
