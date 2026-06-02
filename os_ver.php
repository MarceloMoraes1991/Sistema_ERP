<?php
require_once("db/conexao.php");

$cod = (int)($_GET['cod'] ?? 0);
if ($cod === 0) { header("Location: clientes.php"); exit; }

$r = mysqli_query($con,
    "SELECT os.*, c.nome_completo AS cliente_nome, c.email AS cliente_email,
            c.celular AS cliente_tel, c.cpf_cnpj AS cliente_nif,
            c.endereco, c.cidade, c.estado, c.cep,
            c.cod AS cliente_cod
     FROM ordens_servico os
     JOIN clientes c ON os.cliente_cod = c.cod
     WHERE os.cod=$cod");
if (!$r || mysqli_num_rows($r) === 0) { header("Location: clientes.php"); exit; }
$os = mysqli_fetch_assoc($r);

$imprimir = isset($_GET['imprimir']);

$badge_status = [
    'aberta'       => ['#FFF7ED','#EA580C','Aberta'],
    'em_andamento' => ['#EFF6FF','#2563EB','Em curso'],
    'aguardando'   => ['#FEF9C3','#CA8A04','Aguardando'],
    'concluida'    => ['#DCFCE7','#16A34A','Concluída'],
    'cancelada'    => ['#F1F5F9','#64748B','Cancelada'],
];
[$bg_st,$cor_st,$label_st] = $badge_status[$os['status']] ?? ['#F1F5F9','#64748B',ucfirst($os['status'])];

$prio_label = ['baixa'=>'Baixa','normal'=>'Normal','alta'=>'Alta','urgente'=>'URGENTE'];
$prio_cor   = ['baixa'=>'#64748B','normal'=>'#2563EB','alta'=>'#EA580C','urgente'=>'#DC2626'];

if (!$imprimir) require_once("header.php");
?>

<?php if ($imprimir): ?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($os['numero']) ?> — ITM Technology</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Inter',sans-serif;font-size:13px;color:#1E293B;background:#fff;}
    .page{max-width:794px;margin:0 auto;padding:36px;}
    @media print { .page{padding:20px;} }
  </style>
</head>
<body><div class="page">
<?php endif; ?>

<?php if (!$imprimir): ?>
<main class="pagina">
  <!-- Barra de acções -->
  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="clientes_detalhe.php?cod=<?= $os['cliente_cod'] ?>&aba=ordens" class="btn-icone">
        <i class="material-icons-round">arrow_back</i>
      </a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo"><?= htmlspecialchars($os['numero']) ?></h1>
        <p class="pagina-subtitulo"><?= htmlspecialchars($os['titulo']) ?></p>
      </div>
    </div>
    <div class="page-header-acoes">
      <a href="os_ver.php?cod=<?= $cod ?>&imprimir=1" target="_blank" class="btn btn-outline btn-sm">
        <i class="material-icons-round">print</i> Imprimir / PDF
      </a>
      <a href="os_form.php?cod=<?= $cod ?>&cliente_cod=<?= $os['cliente_cod'] ?>" class="btn btn-outline btn-sm">
        <i class="material-icons-round">edit</i> Editar
      </a>
    </div>
  </div>
  <div class="card">
<?php endif; ?>

<!-- ══ DOCUMENTO ══════════════════════════════════════════ -->
<div style="padding:<?= $imprimir?'0':'32px' ?>;">

  <!-- Cabeçalho -->
  <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:28px;padding-bottom:18px;border-bottom:3px solid #2563EB;">
    <div>
      <?php if ($imprimir): ?>
      <div style="font-size:26px;font-weight:800;color:#2563EB;letter-spacing:-1px;">ITM</div>
      <div style="font-size:11px;font-weight:600;letter-spacing:3px;color:#64748B;text-transform:uppercase;margin-bottom:10px;">TECHNOLOGY</div>
      <?php else: ?>
      <img src="assets/img/logo.svg" style="height:36px;margin-bottom:10px;" alt="ITM Technology">
      <?php endif; ?>
      <div style="font-size:11px;color:#64748B;line-height:1.8;">
        ITM Technology · info@itm.pt
      </div>
    </div>
    <div style="text-align:right;">
      <div style="font-size:22px;font-weight:800;color:#1E293B;">ORDEM DE SERVIÇO</div>
      <div style="font-size:16px;font-weight:700;color:#2563EB;font-family:monospace;margin-top:4px;"><?= htmlspecialchars($os['numero']) ?></div>
      <div style="margin-top:10px;font-size:12px;color:#64748B;line-height:1.8;">
        <div><strong>Abertura:</strong> <?= date('d/m/Y H:i',strtotime($os['data_abertura'])) ?></div>
        <?php if ($os['data_prevista']): ?>
        <div><strong>Prazo:</strong> <?= date('d/m/Y',strtotime($os['data_prevista'])) ?></div>
        <?php endif; ?>
        <?php if ($os['data_conclusao']): ?>
        <div><strong>Conclusão:</strong> <?= date('d/m/Y',strtotime($os['data_conclusao'])) ?></div>
        <?php endif; ?>
        <div style="margin-top:6px;">
          <span style="background:<?= $bg_st ?>;color:<?= $cor_st ?>;padding:3px 12px;border-radius:99px;font-size:11px;font-weight:700;">
            <?= $label_st ?>
          </span>
          <span style="background:#F1F5F9;color:<?= $prio_cor[$os['prioridade']]??'#64748B' ?>;padding:3px 12px;border-radius:99px;font-size:11px;font-weight:700;margin-left:6px;">
            <?= $prio_label[$os['prioridade']]??ucfirst($os['prioridade']) ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Cliente e Técnico -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
    <div style="background:#F8FAFC;border-radius:8px;padding:16px;">
      <div style="font-size:10px;font-weight:700;letter-spacing:.10em;text-transform:uppercase;color:#94A3B8;margin-bottom:10px;">Cliente</div>
      <div style="font-size:15px;font-weight:700;color:#1E293B;"><?= htmlspecialchars($os['cliente_nome']) ?></div>
      <?php if ($os['cliente_nif']): ?>
      <div style="font-size:12px;color:#64748B;margin-top:2px;">NIF: <?= htmlspecialchars($os['cliente_nif']) ?></div>
      <?php endif; ?>
      <?php if ($os['cliente_email']): ?>
      <div style="font-size:12px;color:#64748B;"><?= htmlspecialchars($os['cliente_email']) ?></div>
      <?php endif; ?>
      <?php if ($os['cliente_tel']): ?>
      <div style="font-size:12px;color:#64748B;"><?= htmlspecialchars($os['cliente_tel']) ?></div>
      <?php endif; ?>
    </div>
    <div style="background:#F8FAFC;border-radius:8px;padding:16px;">
      <div style="font-size:10px;font-weight:700;letter-spacing:.10em;text-transform:uppercase;color:#94A3B8;margin-bottom:10px;">Informação técnica</div>
      <?php if ($os['tipo_servico']): ?>
      <div style="font-size:13px;font-weight:600;color:#1E293B;"><?= htmlspecialchars($os['tipo_servico']) ?></div>
      <?php endif; ?>
      <?php if ($os['tecnico']): ?>
      <div style="font-size:12px;color:#64748B;margin-top:4px;"><strong>Técnico:</strong> <?= htmlspecialchars($os['tecnico']) ?></div>
      <?php endif; ?>
      <?php if ($os['equipamento']): ?>
      <div style="font-size:12px;color:#64748B;margin-top:4px;"><strong>Equipamento:</strong> <?= htmlspecialchars($os['equipamento']) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Problema relatado -->
  <?php if ($os['problema_relatado']): ?>
  <div style="margin-bottom:20px;">
    <div style="font-size:10px;font-weight:700;letter-spacing:.10em;text-transform:uppercase;color:#94A3B8;margin-bottom:8px;">Problema relatado pelo cliente</div>
    <div style="font-size:13px;color:#475569;line-height:1.7;padding:14px;background:#FFFBEB;border-left:3px solid #F59E0B;border-radius:0 6px 6px 0;">
      <?= nl2br(htmlspecialchars($os['problema_relatado'])) ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Serviço realizado -->
  <?php if ($os['servico_realizado']): ?>
  <div style="margin-bottom:20px;">
    <div style="font-size:10px;font-weight:700;letter-spacing:.10em;text-transform:uppercase;color:#94A3B8;margin-bottom:8px;">Serviço realizado / Diagnóstico</div>
    <div style="font-size:13px;color:#475569;line-height:1.7;padding:14px;background:#F0FDF4;border-left:3px solid #16A34A;border-radius:0 6px 6px 0;">
      <?= nl2br(htmlspecialchars($os['servico_realizado'])) ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Peças utilizadas -->
  <?php if ($os['pecas_utilizadas']): ?>
  <div style="margin-bottom:20px;">
    <div style="font-size:10px;font-weight:700;letter-spacing:.10em;text-transform:uppercase;color:#94A3B8;margin-bottom:8px;">Peças / Materiais utilizados</div>
    <div style="font-size:13px;color:#475569;line-height:1.7;padding:14px;background:#F8FAFC;border-left:3px solid #CBD5E1;border-radius:0 6px 6px 0;">
      <?= nl2br(htmlspecialchars($os['pecas_utilizadas'])) ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Valores -->
  <div style="display:flex;justify-content:flex-end;margin-bottom:28px;">
    <div style="width:260px;">
      <?php if ($os['valor_servico'] > 0): ?>
      <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13px;border-bottom:1px solid #E2E8F0;color:#64748B;">
        <span>Mão de obra / Serviço</span>
        <span>€ <?= number_format($os['valor_servico'],2,',','.') ?></span>
      </div>
      <?php endif; ?>
      <?php if ($os['valor_pecas'] > 0): ?>
      <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13px;border-bottom:1px solid #E2E8F0;color:#64748B;">
        <span>Peças / Materiais</span>
        <span>€ <?= number_format($os['valor_pecas'],2,',','.') ?></span>
      </div>
      <?php endif; ?>
      <div style="display:flex;justify-content:space-between;padding:12px 0;font-size:18px;font-weight:800;color:#2563EB;border-top:2px solid #2563EB;margin-top:4px;">
        <span>TOTAL</span>
        <span>€ <?= number_format($os['valor_total'],2,',','.') ?></span>
      </div>
    </div>
  </div>

  <!-- Assinaturas -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:32px;padding-top:24px;border-top:1px solid #E2E8F0;">
    <div>
      <div style="border-top:1px solid #1E293B;padding-top:8px;margin-top:40px;font-size:12px;color:#64748B;text-align:center;">
        Assinatura do Cliente<br><?= htmlspecialchars($os['cliente_nome']) ?>
      </div>
    </div>
    <div>
      <div style="border-top:1px solid #1E293B;padding-top:8px;margin-top:40px;font-size:12px;color:#64748B;text-align:center;">
        Assinatura do Técnico<?php if ($os['tecnico']): ?><br><?= htmlspecialchars($os['tecnico']) ?><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Rodapé -->
  <div style="margin-top:24px;text-align:center;font-size:11px;color:#94A3B8;border-top:1px solid #E2E8F0;padding-top:14px;">
    ITM Technology · info@itm.pt
    · OS gerada em <?= date('d/m/Y \à\s H:i') ?>
  </div>
</div>
<!-- ══ /DOCUMENTO ══════════════════════════════════════════ -->

<?php if (!$imprimir): ?>
  </div><!-- /card -->
</main>
<?php require_once("footer.php"); ?>
<?php else: ?>
</div></div>
<script>window.onload=()=>window.print();</script>
</body></html>
<?php endif; ?>
