<?php
$cod = (int)($_GET['cod'] ?? $_GET['id'] ?? 0);
if ($cod === 0) { header("Location: listar_equipamento.php"); exit; }
header("Location: adicionar_equipamento.php?cod=$cod");
exit;
