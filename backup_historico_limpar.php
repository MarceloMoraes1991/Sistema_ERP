<?php
require_once("db/conexao.php");
require_once("bloqueio.php");
if ((int)$_SESSION['perfil'] !== 1) { header("Location: dashboard.php"); exit; }

mysqli_query($con, "DELETE FROM backup_historico");
header("Location: backup.php?msg=ok");
exit;
