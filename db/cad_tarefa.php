<?php
// db/cad_tarefa.php — mantido por compatibilidade
// O cadastro_tarefa.php já processa o POST internamente.
// Este ficheiro redireciona caso alguém acesse diretamente.
require_once("conexao.php");
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../home.php"); exit;
}

$titulo      = $_POST['titulo']    ?? '';
$data        = $_POST['data']      ?? '';
$hora        = $_POST['hora']      ?? '';
$descricao   = $_POST['descricao'] ?? '';
$categoria   = (int)($_POST['categoria']    ?? 0);
$usuario_cod = (int)($_POST['usuario_cod']  ?? $_SESSION['cod'] ?? 0);

$t  = mysqli_real_escape_string($con, $titulo);
$d  = mysqli_real_escape_string($con, $data);
$h  = mysqli_real_escape_string($con, $hora);
$de = mysqli_real_escape_string($con, $descricao);

$sql = "INSERT INTO tarefas (titulo, data, hora, descricao, usuario_cod, categoria_cod)
        VALUES ('$t', '$d', '$h', '$de', $usuario_cod, $categoria)";
mysqli_query($con, $sql);
header("Location: ../home.php?msg=criada");
exit;
