<?php
require_once("db/conexao.php");
require_once("bloqueio.php");

$cod = (int)($_GET['cod'] ?? 0);
if ($cod === 0) { header("Location: clientes.php"); exit; }

$r = mysqli_query($con, "SELECT cod, nome_completo FROM clientes WHERE cod=$cod");
if (!$r || mysqli_num_rows($r) === 0) { header("Location: clientes.php?msg=erro"); exit; }

mysqli_query($con, "DELETE FROM clientes WHERE cod=$cod");

header("Location: clientes.php?msg=excluido");
exit;
