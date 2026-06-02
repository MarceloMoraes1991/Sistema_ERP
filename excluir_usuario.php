<?php
// excluir_usuario.php
require_once("db/conexao.php");
require_once("bloqueio.php");
if ((int)$_SESSION['perfil'] !== 1) { header("Location: dashboard.php"); exit; }
$id = (int)($_GET['id'] ?? 0);
if ($id===0 || $id===(int)$_SESSION['cod']) { header("Location: lista_usuarios.php"); exit; }
mysqli_query($con,"DELETE FROM usuario WHERE cod=$id");
header("Location: lista_usuarios.php?msg=excluido"); exit;
