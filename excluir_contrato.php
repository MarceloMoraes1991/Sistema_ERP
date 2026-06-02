<?php
require_once("db/conexao.php");
require_once("bloqueio.php");
$cod = (int)($_GET['cod'] ?? 0);
if ($cod === 0) { header("Location: listar_contratos.php"); exit; }
$r = mysqli_fetch_assoc(mysqli_query($con, "SELECT arquivo FROM contratos WHERE cod=$cod"));
if ($r && $r['arquivo'] && file_exists($r['arquivo'])) @unlink($r['arquivo']);
mysqli_query($con, "DELETE FROM contratos WHERE cod=$cod");
header("Location: listar_contratos.php?msg=excluido"); exit;
