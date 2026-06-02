<?php
session_start();
if (!isset($_SESSION['email']) || !isset($_SESSION['perfil'])) {
    header("Location: index.php?erro=1");
    exit;
}
require_once("db/conexao.php");
