<?php
require_once("conexao.php");
session_start();

$email = trim($_POST['login'] ?? '');
$senha = md5(trim($_POST['senha'] ?? ''));

if ($email === '' || !$senha) {
    header("Location: ../index.php?erro=2"); exit;
}

$em  = mysqli_real_escape_string($con, $email);
$res = mysqli_query($con,
    "SELECT u.*, p.nome AS perfil_nome
     FROM usuario u
     LEFT JOIN perfil_usuario p ON u.perfil_cod = p.cod
     WHERE u.email='$em' AND u.senha='$senha'
     LIMIT 1");

if ($res && mysqli_num_rows($res) > 0) {
    $d = mysqli_fetch_assoc($res);
    $_SESSION['cod']    = $d['cod'];
    $_SESSION['nome']   = $d['nome'];
    $_SESSION['email']  = $d['email'];
    $_SESSION['perfil'] = (int)$d['perfil_cod'];
    header("Location: ../dashboard.php"); exit;
} else {
    header("Location: ../index.php?erro=2"); exit;
}
