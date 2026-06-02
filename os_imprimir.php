<?php
// Redireciona para os_ver.php com flag de impressão
$cod = (int)($_GET['cod'] ?? 0);
if ($cod === 0) { header("Location: clientes.php"); exit; }
header("Location: os_ver.php?cod=$cod&imprimir=1");
exit;
