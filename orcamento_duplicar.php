<?php
require_once("db/conexao.php");
require_once("bloqueio.php");

$cod = (int)($_GET['cod'] ?? 0);
if ($cod === 0) { header("Location: orcamentos.php"); exit; }

$r = mysqli_query($con, "SELECT * FROM orcamentos WHERE cod=$cod");
if (!$r || mysqli_num_rows($r) === 0) { header("Location: orcamentos.php"); exit; }
$orc = mysqli_fetch_assoc($r);

// Gera novo número
$ano = date('Y');
$seq = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) AS t FROM orcamentos WHERE YEAR(criado_em)=$ano"))['t'] + 1;
$novo_numero = "ORC-$ano-" . str_pad($seq, 4, '0', STR_PAD_LEFT);

$uc = (int)$_SESSION['cod'];

$esc = fn($v) => mysqli_real_escape_string($con, $v ?? '');

// Duplica cabeçalho (estado = Rascunho, data = hoje)
mysqli_query($con, "INSERT INTO orcamentos
    (numero,cliente_cod,cliente_nome,cliente_email,cliente_telef,titulo,descricao,
     estado_cod,validade,data_emissao,subtotal,desconto_pct,desconto_valor,
     iva_pct,iva_valor,total,notas,termos,usuario_cod)
    VALUES (
        '{$esc($novo_numero)}',
        ".($orc['cliente_cod']?:0).",
        '{$esc($orc['cliente_nome'])}',
        '{$esc($orc['cliente_email'])}',
        '{$esc($orc['cliente_telef'])}',
        'Cópia de — {$esc($orc['titulo'])}',
        '{$esc($orc['descricao'])}',
        1,
        NULL,
        '".date('Y-m-d')."',
        {$orc['subtotal']},{$orc['desconto_pct']},{$orc['desconto_valor']},
        {$orc['iva_pct']},{$orc['iva_valor']},{$orc['total']},
        '{$esc($orc['notas'])}','{$esc($orc['termos'])}',$uc
    )");
$novo_cod = mysqli_insert_id($con);

// Duplica linhas
$linhas = mysqli_query($con, "SELECT * FROM orcamento_linhas WHERE orcamento_cod=$cod ORDER BY ordem");
while ($l = mysqli_fetch_assoc($linhas)) {
    $desc = $esc($l['descricao']);
    $un   = $esc($l['unidade']);
    mysqli_query($con, "INSERT INTO orcamento_linhas
        (orcamento_cod,ordem,descricao,quantidade,unidade,preco_unit,desconto_pct,total_linha)
        VALUES ($novo_cod,{$l['ordem']},'$desc',{$l['quantidade']},'$un',
        {$l['preco_unit']},{$l['desconto_pct']},{$l['total_linha']})");
}

header("Location: orcamento_form.php?cod=$novo_cod");
exit;
