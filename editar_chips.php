<?php
$cod = (int)($_GET['cod'] ?? $_GET['id'] ?? 0);
if ($cod === 0) { header("Location: chips.php"); exit; }
header("Location: cadastro_chips.php?cod=$cod");
exit;
