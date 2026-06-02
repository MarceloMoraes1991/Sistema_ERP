<?php
require_once("db/conexao.php");

$cod = (int)($_GET['cod'] ?? 0);
if ($cod === 0) { header("Location: orcamentos.php"); exit; }

$r = mysqli_query($con,
    "SELECT o.*, e.nome AS estado_nome, e.cor AS estado_cor,
            u.nome AS criado_por
     FROM orcamentos o
     LEFT JOIN orcamento_estados e ON o.estado_cod = e.cod
     LEFT JOIN usuario u ON o.usuario_cod = u.cod
     WHERE o.cod = $cod");
if (!$r || mysqli_num_rows($r) === 0) { header("Location: orcamentos.php"); exit; }
$orc = mysqli_fetch_assoc($r);

$linhas = mysqli_query($con,
    "SELECT * FROM orcamento_linhas WHERE orcamento_cod=$cod ORDER BY ordem");

$imprimir = isset($_GET['imprimir']);

// Flash
$msgs = [
    'criado'  => ['sucesso','Orçamento criado com sucesso!'],
    'editado' => ['sucesso','Orçamento actualizado com sucesso!'],
];
[$mt, $mm] = $msgs[$_GET['msg'] ?? ''] ?? ['',''];

// Badge estado
$badge_map = [
    'cinza'    => 'badge-cinza',
    'azul'     => 'badge-azul',
    'laranja'  => 'badge-laranja',
    'verde'    => 'badge-verde',
    'vermelho' => 'badge-vermelho',
    'amarelo'  => 'badge-amarelo',
];
$badge = $badge_map[$orc['estado_cor']] ?? 'badge-cinza';

if (!$imprimir) require_once("header.php");
?>
<!DOCTYPE html>
<?php if ($imprimir): ?>
<html lang="pt-PT">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($orc['numero']) ?> — ITM Technology</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; font-size: 13px; color: #1E293B; background: #fff; }
    .page { max-width: 794px; margin: 0 auto; padding: 40px; }
  </style>
</head>
<body>
<div class="page">
<?php endif; ?>

<?php if (!$imprimir): ?>
<main class="pagina">

  <?php if ($mm): ?>
  <div class="alerta alerta-<?= $mt ?> mb-20">
    <i class="material-icons-round"><?= $mt==='sucesso'?'check_circle':'warning' ?></i>
    <span><?= htmlspecialchars($mm) ?></span>
    <button data-fechar-alerta style="margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;">
      <i class="material-icons-round" style="font-size:18px;">close</i>
    </button>
  </div>
  <?php endif; ?>

  <!-- Barra de acções -->
  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="orcamentos.php" class="btn-icone"><i class="material-icons-round">arrow_back</i></a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo"><?= htmlspecialchars($orc['numero']) ?></h1>
        <p class="pagina-subtitulo"><?= htmlspecialchars($orc['titulo']) ?></p>
      </div>
    </div>
    <div class="page-header-acoes">
      <!-- Alterar estado rápido -->
      <form method="POST" action="orcamento_estado.php" style="display:flex;gap:8px;align-items:center;">
        <input type="hidden" name="cod" value="<?= $orc['cod'] ?>">
        <select name="estado_cod" class="form-select" style="width:150px;font-size:13px;padding:7px 10px;">
          <?php
          $estados_q = mysqli_query($con, "SELECT * FROM orcamento_estados ORDER BY cod");
          while ($est = mysqli_fetch_assoc($estados_q)):
          ?>
          <option value="<?= $est['cod'] ?>" <?= (int)$orc['estado_cod']===(int)$est['cod']?'selected':'' ?>>
            <?= htmlspecialchars($est['nome']) ?>
          </option>
          <?php endwhile; ?>
        </select>
        <button type="submit" class="btn btn-outline btn-sm">
          <i class="material-icons-round">sync</i> Actualizar
        </button>
      </form>
      <a href="orcamento_ver.php?cod=<?= $cod ?>&imprimir=1" target="_blank" class="btn btn-outline btn-sm">
        <i class="material-icons-round">print</i> Imprimir / PDF
      </a>
      <a href="orcamento_form.php?cod=<?= $cod ?>" class="btn btn-outline btn-sm">
        <i class="material-icons-round">edit</i> Editar
      </a>
      <a href="orcamento_excluir.php?cod=<?= $cod ?>"
         class="btn btn-perigo btn-sm"
         onclick="return confirm('Eliminar este orçamento?')">
        <i class="material-icons-round">delete</i>
      </a>
    </div>
  </div>

  <div class="card">
<?php endif; ?>

  <!-- ══ DOCUMENTO DO ORÇAMENTO ══════════════════════════════ -->
  <div style="padding:<?= $imprimir?'0':'32px' ?>;">

    <!-- Cabeçalho do documento -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;padding-bottom:20px;border-bottom:3px solid #2563EB;">
      <div>
        <?php if ($imprimir): ?>
        <div style="font-size:28px;font-weight:800;color:#2563EB;letter-spacing:-1px;">ITM</div>
        <div style="font-size:11px;font-weight:600;letter-spacing:3px;color:#64748B;text-transform:uppercase;">TECHNOLOGY</div>
        <?php else: ?>
        <img src="assets/img/logo.svg" alt="ITM Technology" style="height:40px;">
        <?php endif; ?>
        <div style="margin-top:12px;font-size:12px;color:#64748B;line-height:1.7;">
          ITM Technology<br>
          NIF: PT000000000<br>
          info@itm.pt
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:28px;font-weight:800;color:#1E293B;letter-spacing:-0.5px;">ORÇAMENTO</div>
        <div style="font-size:16px;font-weight:600;color:#2563EB;font-family:monospace;margin-top:4px;">
          <?= htmlspecialchars($orc['numero']) ?>
        </div>
        <div style="margin-top:12px;font-size:12px;color:#64748B;line-height:1.8;">
          <div><strong>Emissão:</strong> <?= date('d/m/Y', strtotime($orc['data_emissao'])) ?></div>
          <?php if ($orc['validade']): ?>
          <div><strong>Validade:</strong>
            <span style="color:<?= $orc['validade'] < date('Y-m-d') && $orc['estado_cod'] < 4 ? '#DC2626' : '#1E293B' ?>">
              <?= date('d/m/Y', strtotime($orc['validade'])) ?>
            </span>
          </div>
          <?php endif; ?>
          <div style="margin-top:6px;">
            <?php
            $badge_cores = [
                'verde'    => ['#DCFCE7','#16A34A'],
                'azul'     => ['#EFF6FF','#2563EB'],
                'laranja'  => ['#FFF7ED','#EA580C'],
                'vermelho' => ['#FEE2E2','#DC2626'],
                'amarelo'  => ['#FEF3C7','#D97706'],
                'cinza'    => ['#F1F5F9','#64748B'],
            ];
            [$bg, $cor] = $badge_cores[$orc['estado_cor']] ?? ['#F1F5F9','#64748B'];
            ?>
            <span style="background:<?= $bg ?>;color:<?= $cor ?>;padding:3px 12px;border-radius:99px;font-size:11px;font-weight:700;">
              <?= htmlspecialchars($orc['estado_nome']) ?>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Dados do cliente -->
    <div style="margin-bottom:28px;">
      <div style="font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#94A3B8;margin-bottom:8px;">Cliente</div>
      <div style="font-size:15px;font-weight:700;color:#1E293B;"><?= htmlspecialchars($orc['cliente_nome']) ?></div>
      <?php if ($orc['cliente_email']): ?>
      <div style="font-size:13px;color:#64748B;margin-top:2px;"><?= htmlspecialchars($orc['cliente_email']) ?></div>
      <?php endif; ?>
      <?php if ($orc['cliente_telef']): ?>
      <div style="font-size:13px;color:#64748B;"><?= htmlspecialchars($orc['cliente_telef']) ?></div>
      <?php endif; ?>
    </div>

    <!-- Título e descrição -->
    <div style="margin-bottom:24px;">
      <div style="font-size:16px;font-weight:700;color:#1E293B;"><?= htmlspecialchars($orc['titulo']) ?></div>
      <?php if ($orc['descricao']): ?>
      <div style="font-size:13px;color:#64748B;margin-top:6px;line-height:1.6;"><?= nl2br(htmlspecialchars($orc['descricao'])) ?></div>
      <?php endif; ?>
    </div>

    <!-- Tabela de itens -->
    <table style="width:100%;border-collapse:collapse;margin-bottom:24px;font-size:13px;">
      <thead>
        <tr style="background:#1E293B;color:#fff;">
          <th style="padding:10px 14px;text-align:left;font-weight:600;border-radius:6px 0 0 0;">Descrição</th>
          <th style="padding:10px 14px;text-align:center;font-weight:600;white-space:nowrap;">Qtd.</th>
          <th style="padding:10px 14px;text-align:center;font-weight:600;">Un.</th>
          <th style="padding:10px 14px;text-align:right;font-weight:600;white-space:nowrap;">Preço unit.</th>
          <th style="padding:10px 14px;text-align:center;font-weight:600;">Desc.%</th>
          <th style="padding:10px 14px;text-align:right;font-weight:600;border-radius:0 6px 0 0;">Total</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $i = 0;
      while ($l = mysqli_fetch_assoc($linhas)):
        $bg_row = $i % 2 === 0 ? '#F8FAFC' : '#fff';
        $i++;
      ?>
      <tr style="background:<?= $bg_row ?>;">
        <td style="padding:10px 14px;border-bottom:1px solid #E2E8F0;"><?= htmlspecialchars($l['descricao']) ?></td>
        <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #E2E8F0;"><?= number_format($l['quantidade'],2,',','.') ?></td>
        <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #E2E8F0;color:#64748B;"><?= htmlspecialchars($l['unidade']) ?></td>
        <td style="padding:10px 14px;text-align:right;border-bottom:1px solid #E2E8F0;">€ <?= number_format($l['preco_unit'],2,',','.') ?></td>
        <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #E2E8F0;color:#64748B;">
          <?= $l['desconto_pct'] > 0 ? number_format($l['desconto_pct'],1,',','.').'%' : '—' ?>
        </td>
        <td style="padding:10px 14px;text-align:right;font-weight:600;border-bottom:1px solid #E2E8F0;">€ <?= number_format($l['total_linha'],2,',','.') ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>

    <!-- Totais -->
    <div style="display:flex;justify-content:flex-end;margin-bottom:28px;">
      <div style="width:280px;">
        <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13px;border-bottom:1px solid #E2E8F0;color:#64748B;">
          <span>Subtotal</span>
          <span>€ <?= number_format($orc['subtotal'],2,',','.') ?></span>
        </div>
        <?php if ($orc['desconto_pct'] > 0): ?>
        <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13px;border-bottom:1px solid #E2E8F0;color:#EA580C;">
          <span>Desconto (<?= number_format($orc['desconto_pct'],1,',','.')  ?>%)</span>
          <span>— € <?= number_format($orc['desconto_valor'],2,',','.') ?></span>
        </div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13px;border-bottom:1px solid #E2E8F0;color:#64748B;">
          <span>Base tributável</span>
          <span>€ <?= number_format($orc['subtotal'] - $orc['desconto_valor'],2,',','.') ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13px;border-bottom:1px solid #E2E8F0;color:#64748B;">
          <span>IVA (<?= number_format($orc['iva_pct'],0) ?>%)</span>
          <span>€ <?= number_format($orc['iva_valor'],2,',','.') ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:12px 0;font-size:18px;font-weight:800;color:#2563EB;border-top:2px solid #2563EB;margin-top:4px;">
          <span>TOTAL</span>
          <span>€ <?= number_format($orc['total'],2,',','.') ?></span>
        </div>
      </div>
    </div>

    <!-- Notas e Termos -->
    <?php if ($orc['notas'] || $orc['termos']): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
      <?php if ($orc['notas']): ?>
      <div>
        <div style="font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#94A3B8;margin-bottom:8px;">Observações</div>
        <div style="font-size:12.5px;color:#475569;line-height:1.7;"><?= nl2br(htmlspecialchars($orc['notas'])) ?></div>
      </div>
      <?php endif; ?>
      <?php if ($orc['termos']): ?>
      <div>
        <div style="font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#94A3B8;margin-bottom:8px;">Termos e Condições</div>
        <div style="font-size:12.5px;color:#475569;line-height:1.7;"><?= nl2br(htmlspecialchars($orc['termos'])) ?></div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Rodapé do documento -->
    <div style="border-top:1px solid #E2E8F0;padding-top:16px;text-align:center;font-size:11px;color:#94A3B8;">
      ITM Technology · info@itm.pt
      <?php if ($orc['validade']): ?>
       · Proposta válida até <?= date('d/m/Y', strtotime($orc['validade'])) ?>
      <?php endif; ?>
       · Documento gerado em <?= date('d/m/Y \à\s H:i') ?>
    </div>

  </div><!-- /documento -->

<?php if (!$imprimir): ?>
  </div><!-- /card -->
</main>
<?php require_once("footer.php"); ?>
<?php else: ?>
</div><!-- /page -->
<script>window.onload = () => window.print();</script>
</body>
</html>
<?php endif; ?>
