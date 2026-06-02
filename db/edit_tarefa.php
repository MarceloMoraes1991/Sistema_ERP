<?php
// db/edit_tarefa.php — mantido por compatibilidade
// O cadastro_tarefa.php já processa edições internamente via ?cod=
require_once("conexao.php");
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../home.php"); exit;
}

$cod       = (int)($_POST['cod']        ?? 0);
$titulo    = $_POST['titulo']           ?? '';
$data      = $_POST['data']             ?? '';
$hora      = $_POST['hora']             ?? '';
$descricao = $_POST['descricao']        ?? '';
$categoria = (int)($_POST['categoria']  ?? 0);

if ($cod === 0) { header("Location: ../home.php"); exit; }

$t  = mysqli_real_escape_string($con, $titulo);
$d  = mysqli_real_escape_string($con, $data);
$h  = mysqli_real_escape_string($con, $hora);
$de = mysqli_real_escape_string($con, $descricao);

mysqli_query($con, "UPDATE tarefas SET titulo='$t', data='$d', hora='$h', descricao='$de', categoria_cod=$categoria WHERE cod=$cod");
header("Location: ../home.php?msg=editada");
exit;
