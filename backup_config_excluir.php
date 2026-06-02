<?php
// backup_config_excluir.php
require_once("db/conexao.php");
require_once("bloqueio.php");
if ((int)$_SESSION['perfil'] !== 1) { header("Location: dashboard.php"); exit; }

$cod = (int)($_GET['cod'] ?? 0);
if ($cod === 0) { header("Location: backup.php"); exit; }

mysqli_query($con, "DELETE FROM backup_configs WHERE cod=$cod");
header("Location: backup.php?msg=del");
exit;
