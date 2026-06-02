<?php
require_once("db/conexao.php");
require_once("bloqueio.php");

$cod         = (int)($_GET['cod']         ?? 0);
$cliente_cod = (int)($_GET['cliente_cod'] ?? 0);

if ($cod === 0) { header("Location: clientes.php"); exit; }

mysqli_query($con, "DELETE FROM financeiro WHERE cod=$cod");

header("Location: clientes_detalhe.php?cod=$cliente_cod&aba=financeiro");
exit;
