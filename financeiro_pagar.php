<?php
// financeiro_pagar.php — Marca lançamento como pago
require_once("db/conexao.php");
require_once("bloqueio.php");

$cod         = (int)($_GET['cod']         ?? 0);
$cliente_cod = (int)($_GET['cliente_cod'] ?? 0);

if ($cod === 0) { header("Location: clientes.php"); exit; }

mysqli_query($con,
    "UPDATE financeiro SET
        status='pago',
        data_pagamento=CURDATE()
     WHERE cod=$cod");

header("Location: clientes_detalhe.php?cod=$cliente_cod&aba=financeiro&msg=fin_ok");
exit;
