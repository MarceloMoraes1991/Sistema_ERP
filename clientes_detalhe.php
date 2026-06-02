<?php
require_once("db/conexao.php");

$cod = (int)($_GET['cod'] ?? 0);
if ($cod === 0) { header("Location: clientes.php"); exit; }

$r = mysqli_query($con, "SELECT * FROM clientes WHERE cod=$cod");
if (!$r || mysqli_num_rows($r) === 0) { header("Location: clientes.php?msg=erro"); exit; }
$c = mysqli_fetch_assoc($r);

$aba = $_GET['aba'] ?? 'ficha';

// Orçamentos do cliente
$orcamentos = mysqli_query($con,
    "SELECT o.*, e.nome AS estado_nome, e.cor AS estado_cor
     FROM orcamentos o
     LEFT JOIN orcamento_estados e ON o.estado_cod = e.cod
     WHERE o.cliente_cod=$cod ORDER BY o.criado_em DESC LIMIT 10");
$total_orc   = (int)(mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) AS t FROM orcamentos WHERE cliente_cod=$cod"))['t']??0);
$valor_orc   = mysqli_fetch_assoc(mysqli_query($con,"SELECT COALESCE(SUM(total),0) AS t FROM orcamentos WHERE cliente_cod=$cod"))['t']??0;

// Ordens de Serviço
$ordens = mysqli_query($con,
    "SELECT * FROM ordens_servico WHERE cliente_cod=$cod ORDER BY data_abertura DESC LIMIT 20");
$total_os    = (int)(mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) AS t FROM ordens_servico WHERE cliente_cod=$cod"))['t']??0);
$os_abertas  = (int)(mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) AS t FROM ordens_servico WHERE cliente_cod=$cod AND status IN ('aberta','em_andamento','aguardando')"))['t']??0);

// Financeiro
$lancamentos = mysqli_query($con,
    "SELECT * FROM financeiro WHERE cliente_cod=$cod ORDER BY data_lancamento DESC LIMIT 20");
$fin_tot = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT
        COALESCE(SUM(CASE WHEN tipo='receita' THEN valor ELSE 0 END),0) AS receitas,
        COALESCE(SUM(CASE WHEN tipo='despesa' THEN valor ELSE 0 END),0) AS despesas,
        COALESCE(SUM(CASE WHEN status='pendente' AND tipo='receita' THEN valor ELSE 0 END),0) AS pendente,
        COALESCE(SUM(CASE WHEN status='vencido' AND tipo='receita' THEN valor ELSE 0 END),0) AS vencido
     FROM financeiro WHERE cliente_cod=$cod")) ?? [];

// Flash
$msgs = [
    'criado'   => ['sucesso','Cliente criado com sucesso!'],
    'editado'  => ['sucesso','Dados actualizados com sucesso!'],
    'os_criada'=> ['sucesso','Ordem de serviço criada com sucesso!'],
    'os_edit'  => ['sucesso','Ordem de serviço actualizada!'],
    'fin_ok'   => ['sucesso','Lançamento financeiro registado!'],
];
[$mt, $mm] = $msgs[$_GET['msg'] ?? ''] ?? ['',''];

// Avatar
$p  = explode(' ', trim($c['nome_completo']));
$av = strtoupper(substr($p[0],0,1)).(count($p)>1?strtoupper(substr(end($p),0,1)):'');
$is_jur = $c['tipo_pessoa'] === 'juridica';

require_once("header.php");

$badge_os = [
    'aberta'       => ['badge-laranja','Aberta'],
    'em_andamento' => ['badge-azul',   'Em curso'],
    'aguardando'   => ['badge-amarelo','Aguardando'],
    'concluida'    => ['badge-verde',  'Concluída'],
    'cancelada'    => ['badge-cinza',  'Cancelada'],
];
$prio_badge = [
    'baixa'   => ['badge-cinza',   'Baixa'],
    'normal'  => ['badge-azul',    'Normal'],
    'alta'    => ['badge-laranja', 'Alta'],
    'urgente' => ['badge-vermelho','Urgente'],
];
$badge_fin = [
    'pendente'  => ['badge-laranja','Pendente'],
    'pago'      => ['badge-verde',  'Pago'],
    'vencido'   => ['badge-vermelho','Vencido'],
    'cancelado' => ['badge-cinza',  'Cancelado'],
];
?>

<main class="pagina">

  <?php if ($mm): ?>
  <div class="alerta alerta-<?= $mt ?> mb-20">
    <i class="material-icons-round"><?= $mt==='sucesso'?'check_circle':'error_outline' ?></i>
    <span><?= htmlspecialchars($mm) ?></span>
    <button data-fechar-alerta style="margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;">
      <i class="material-icons-round" style="font-size:18px;">close</i>
    </button>
  </div>
  <?php endif; ?>

  <!-- Cabeçalho do perfil -->
  <div class="card mb-16">
    <div class="card-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
      <div style="width:60px;height:60px;border-radius:50%;
                  background:<?= $is_jur?'var(--roxo)':'var(--azul)' ?>;
                  display:flex;align-items:center;justify-content:center;
                  font-size:22px;font-weight:700;color:#fff;flex-shrink:0;">
        <?= $av ?>
      </div>
      <div style="flex:1;min-width:200px;">
        <div style="font-size:20px;font-weight:700;color:var(--c900);margin-bottom:5px;">
          <?= htmlspecialchars($c['nome_completo']) ?>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
          <span class="badge <?= $is_jur?'badge-roxo':'badge-azul' ?>"><?= $is_jur?'Pessoa Colectiva':'Pessoa Singular' ?></span>
          <span class="badge <?= $c['status']==='ativo'?'badge-verde':'badge-cinza' ?>"><?= $c['status']==='ativo'?'Activo':'Inactivo' ?></span>
          <?php if ($c['cpf_cnpj']): ?>
          <span style="font-size:12px;color:var(--c500);font-family:monospace;">NIF: <?= htmlspecialchars($c['cpf_cnpj']) ?></span>
          <?php endif; ?>
        </div>
        <!-- Resumo rápido -->
        <div style="display:flex;gap:16px;margin-top:8px;flex-wrap:wrap;">
          <span style="font-size:12px;color:var(--c500);">
            <strong style="color:var(--c700);"><?= $total_os ?></strong> OS
            <?php if ($os_abertas > 0): ?>
            <span class="badge badge-laranja" style="font-size:10px;margin-left:3px;"><?= $os_abertas ?> aberta(s)</span>
            <?php endif; ?>
          </span>
          <span style="font-size:12px;color:var(--c500);">
            <strong style="color:var(--c700);"><?= $total_orc ?></strong> Orçamentos
          </span>
          <span style="font-size:12px;color:var(--verde);font-weight:600;">
            € <?= number_format($fin_tot['receitas']??0,2,',','.') ?> recebido
          </span>
          <?php if (($fin_tot['vencido']??0) > 0): ?>
          <span style="font-size:12px;color:var(--vermelho);font-weight:600;">
            € <?= number_format($fin_tot['vencido'],2,',','.') ?> vencido
          </span>
          <?php endif; ?>
        </div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php if ($c['whatsapp']): ?>
        <a href="https://wa.me/351<?= preg_replace('/\D/','',$c['whatsapp']) ?>" target="_blank" class="btn btn-sucesso btn-sm">
          <i class="material-icons-round">chat</i> WhatsApp
        </a>
        <?php endif; ?>
        <a href="os_form.php?cliente_cod=<?= $cod ?>" class="btn btn-outline btn-sm" style="color:var(--laranja);border-color:var(--laranja);">
          <i class="material-icons-round">build_circle</i> Nova OS
        </a>
        <a href="orcamento_form.php?cliente_cod=<?= $cod ?>" class="btn btn-outline btn-sm" style="color:var(--ciano);border-color:var(--ciano);">
          <i class="material-icons-round">request_quote</i> Orçamento
        </a>
        <a href="clientes_form.php?cod=<?= $cod ?>" class="btn btn-primario btn-sm">
          <i class="material-icons-round">edit</i> Editar
        </a>
      </div>
    </div>
  </div>

  <!-- Abas -->
  <div style="display:flex;gap:2px;margin-bottom:16px;border-bottom:2px solid var(--c200);overflow-x:auto;">
    <?php
    $abas = [
      'ficha'      => ['badge','Ficha'],
      'ordens'     => ['build_circle','Ordens de Serviço ('.$total_os.')'],
      'orcamentos' => ['request_quote','Orçamentos ('.$total_orc.')'],
      'financeiro' => ['account_balance_wallet','Financeiro'],
    ];
    foreach ($abas as $key => [$icon, $label]):
    ?>
    <a href="?cod=<?= $cod ?>&aba=<?= $key ?>"
       style="display:flex;align-items:center;gap:6px;padding:10px 16px;font-size:13px;font-weight:500;
              white-space:nowrap;border-bottom:2px solid <?= $aba===$key?'var(--azul)':'transparent' ?>;
              margin-bottom:-2px;color:<?= $aba===$key?'var(--azul)':'var(--c500)' ?>;
              transition:color .15s;text-decoration:none;">
      <i class="material-icons-round" style="font-size:16px;"><?= $icon ?></i>
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- ═══════════ ABA FICHA ═══════════ -->
  <?php if ($aba === 'ficha'): ?>
  <div class="grid-2 mb-16">
    <div class="card">
      <div class="card-header"><span class="card-titulo"><i class="material-icons-round" style="font-size:16px;vertical-align:-3px;">badge</i> Identificação</span></div>
      <div class="card-body" style="padding-top:0;padding-bottom:0;">
        <?php
        function info2($l,$v,$i=''){
          $val=$v?htmlspecialchars($v):'<span style="color:var(--c300);">—</span>';
          $ic=$i?"<i class='material-icons-round' style='font-size:15px;color:var(--c400);flex-shrink:0;margin-top:1px;'>$i</i>":'';
          echo "<div style='padding:9px 0;border-bottom:1px solid var(--c100);display:flex;gap:10px;align-items:flex-start;'>$ic<div style='flex:1;'><div style='font-size:10.5px;font-weight:700;color:var(--c400);text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px;'>$l</div><div style='font-size:13.5px;color:var(--c800);'>$val</div></div></div>";
        }
        info2($is_jur?'NIPC':'NIF',$c['cpf_cnpj'],'fingerprint');
        if (!$is_jur) { info2('Cartão de Cidadão',$c['rg'],'credit_card'); info2('Data de nascimento',$c['data_nascimento']?date('d/m/Y',strtotime($c['data_nascimento'])):'',' cake'); }
        info2('Nacionalidade',$c['nacionalidade'],'public');
        ?>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-titulo"><i class="material-icons-round" style="font-size:16px;vertical-align:-3px;">contact_phone</i> Contactos</span></div>
      <div class="card-body" style="padding-top:0;padding-bottom:0;">
        <?php
        info2('E-mail',$c['email'],'email');
        info2('Telefone',$c['telefone'],'phone');
        info2('Telemóvel',$c['celular'],'smartphone');
        info2('WhatsApp',$c['whatsapp'],'chat');
        ?>
      </div>
    </div>
  </div>
  <!-- Morada -->
  <?php if (array_filter([$c['endereco'],$c['cidade'],$c['cep']])): ?>
  <div class="card mb-16">
    <div class="card-header">
      <span class="card-titulo"><i class="material-icons-round" style="font-size:16px;vertical-align:-3px;">home</i> Morada</span>
      <a href="https://maps.google.com/?q=<?= urlencode(implode(', ',array_filter([$c['endereco'].($c['numero']?', '.$c['numero']:''),$c['bairro'],$c['cidade'],$c['cep']]))) ?>" target="_blank" class="btn btn-outline btn-sm"><i class="material-icons-round">map</i> Ver no mapa</a>
    </div>
    <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
      <?php info2('Código postal',$c['cep']); info2('Rua',$c['endereco'].($c['numero']?', Nº '.$c['numero']:'')); info2('Localidade',$c['bairro']); info2('Concelho',$c['cidade']); info2('Distrito',$c['estado']); ?>
    </div>
  </div>
  <?php endif; ?>
  <?php if ($c['observacoes']): ?>
  <div class="card mb-16">
    <div class="card-header"><span class="card-titulo">Observações</span></div>
    <div class="card-body"><p style="font-size:13.5px;color:var(--c700);line-height:1.7;white-space:pre-wrap;"><?= htmlspecialchars($c['observacoes']) ?></p></div>
  </div>
  <?php endif; ?>

  <!-- ═══════════ ABA ORDENS DE SERVIÇO ═══════════ -->
  <?php elseif ($aba === 'ordens'): ?>
  <div class="metricas mb-20">
    <?php
    $os_m = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) AS total, SUM(status='aberta') AS ab, SUM(status='em_andamento') AS em, SUM(status='concluida') AS conc, COALESCE(SUM(valor_total),0) AS faturado FROM ordens_servico WHERE cliente_cod=$cod"))??[];
    ?>
    <div class="metrica"><div class="metrica-acento acento-azul"></div><div class="metrica-label"><i class="material-icons-round">build_circle</i> Total OS</div><div class="metrica-valor"><?= $os_m['total']??0 ?></div><div class="metrica-sub">Ordens criadas</div></div>
    <div class="metrica"><div class="metrica-acento acento-laranja"></div><div class="metrica-label"><i class="material-icons-round">pending</i> Em aberto</div><div class="metrica-valor"><?= $os_m['ab']??0 ?></div><div class="metrica-sub">Aguardam tratamento</div></div>
    <div class="metrica"><div class="metrica-acento acento-roxo"></div><div class="metrica-label"><i class="material-icons-round">autorenew</i> Em curso</div><div class="metrica-valor"><?= $os_m['em']??0 ?></div><div class="metrica-sub">A decorrer</div></div>
    <div class="metrica"><div class="metrica-acento acento-verde"></div><div class="metrica-label"><i class="material-icons-round">euro</i> Facturado</div><div class="metrica-valor">€ <?= number_format($os_m['faturado']??0,0,',','.') ?></div><div class="metrica-sub">Total em OS</div></div>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-titulo">Ordens de Serviço</span>
      <a href="os_form.php?cliente_cod=<?= $cod ?>" class="btn btn-primario btn-sm">
        <i class="material-icons-round">add</i> Nova OS
      </a>
    </div>
    <?php if (mysqli_num_rows($ordens) === 0): ?>
    <div class="vazio">
      <i class="material-icons-round">build_circle</i>
      <h3>Sem ordens de serviço</h3>
      <p>Ainda não foram criadas OS para este cliente.</p>
      <a href="os_form.php?cliente_cod=<?= $cod ?>" class="btn btn-primario btn-sm"><i class="material-icons-round">add</i> Criar OS</a>
    </div>
    <?php else: ?>
    <div class="tabela-wrap">
      <table class="tabela">
        <thead><tr><th>Nº OS</th><th>Título</th><th>Técnico</th><th>Prioridade</th><th style="text-align:right;">Valor</th><th>Estado</th><th>Data</th><th style="text-align:center;">Acções</th></tr></thead>
        <tbody>
        <?php mysqli_data_seek($ordens,0); while ($os = mysqli_fetch_assoc($ordens)):
          [$bs,$bl] = $badge_os[$os['status']]  ?? ['badge-cinza',ucfirst($os['status'])];
          [$bp,$bpl]= $prio_badge[$os['prioridade']]??['badge-cinza','—'];
        ?>
        <tr>
          <td><span style="font-family:monospace;font-size:12px;font-weight:600;color:var(--azul);"><?= htmlspecialchars($os['numero']) ?></span></td>
          <td><div class="td-nome"><?= htmlspecialchars(mb_strimwidth($os['titulo'],0,45,'…')) ?></div><?php if ($os['tipo_servico']): ?><div class="td-sub"><?= htmlspecialchars($os['tipo_servico']) ?></div><?php endif; ?></td>
          <td style="font-size:13px;color:var(--c600);"><?= $os['tecnico']?htmlspecialchars($os['tecnico']):'<span style="color:var(--c300);">—</span>' ?></td>
          <td><span class="badge <?= $bp ?>" style="font-size:10.5px;"><?= $bpl ?></span></td>
          <td style="text-align:right;font-weight:600;color:var(--c800);">€ <?= number_format($os['valor_total'],2,',','.') ?></td>
          <td><span class="badge <?= $bs ?>"><?= $bl ?></span></td>
          <td style="font-size:12px;color:var(--c500);"><?= date('d/m/Y',strtotime($os['data_abertura'])) ?></td>
          <td>
            <div class="acoes" style="justify-content:center;">
              <a href="os_ver.php?cod=<?= $os['cod'] ?>" class="btn-icone" title="Ver OS" style="color:var(--azul);"><i class="material-icons-round" style="font-size:14px;">visibility</i></a>
              <a href="os_form.php?cod=<?= $os['cod'] ?>&cliente_cod=<?= $cod ?>" class="btn-icone" title="Editar"><i class="material-icons-round" style="font-size:14px;">edit</i></a>
              <a href="os_imprimir.php?cod=<?= $os['cod'] ?>" target="_blank" class="btn-icone" title="Imprimir"><i class="material-icons-round" style="font-size:14px;">print</i></a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ═══════════ ABA ORÇAMENTOS ═══════════ -->
  <?php elseif ($aba === 'orcamentos'): ?>
  <div class="card">
    <div class="card-header">
      <span class="card-titulo">Orçamentos do cliente</span>
      <a href="orcamento_form.php?cliente_cod=<?= $cod ?>" class="btn btn-primario btn-sm">
        <i class="material-icons-round">add</i> Novo orçamento
      </a>
    </div>
    <?php if (mysqli_num_rows($orcamentos) === 0): ?>
    <div class="vazio">
      <i class="material-icons-round">request_quote</i>
      <h3>Sem orçamentos</h3>
      <a href="orcamento_form.php?cliente_cod=<?= $cod ?>" class="btn btn-primario btn-sm"><i class="material-icons-round">add</i> Criar orçamento</a>
    </div>
    <?php else: ?>
    <!-- Resumo -->
    <div style="padding:14px 18px;background:var(--c50);border-bottom:1px solid var(--c200);display:flex;gap:20px;flex-wrap:wrap;">
      <span style="font-size:13px;color:var(--c600);">Total: <strong style="color:var(--c800);">€ <?= number_format($valor_orc,2,',','.') ?></strong></span>
    </div>
    <div class="tabela-wrap">
      <table class="tabela">
        <thead><tr><th>Nº</th><th>Título</th><th>Data</th><th style="text-align:right;">Total</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php
        $bmap=['cinza'=>'badge-cinza','azul'=>'badge-azul','laranja'=>'badge-laranja','verde'=>'badge-verde','vermelho'=>'badge-vermelho','amarelo'=>'badge-amarelo'];
        mysqli_data_seek($orcamentos,0);
        while ($orc=mysqli_fetch_assoc($orcamentos)):
          $b=$bmap[$orc['estado_cor']]??'badge-cinza';
        ?>
        <tr>
          <td><a href="orcamento_ver.php?cod=<?= $orc['cod'] ?>" style="color:var(--azul);font-family:monospace;font-size:12px;font-weight:600;"><?= htmlspecialchars($orc['numero']) ?></a></td>
          <td style="font-size:13px;"><?= htmlspecialchars(mb_strimwidth($orc['titulo'],0,45,'…')) ?></td>
          <td style="font-size:12px;color:var(--c500);"><?= date('d/m/Y',strtotime($orc['data_emissao'])) ?></td>
          <td style="text-align:right;font-weight:600;">€ <?= number_format($orc['total'],2,',','.') ?></td>
          <td><span class="badge <?= $b ?>" style="font-size:10px;"><?= htmlspecialchars($orc['estado_nome']) ?></span></td>
          <td>
            <div class="acoes">
              <a href="orcamento_ver.php?cod=<?= $orc['cod'] ?>" class="btn-icone"><i class="material-icons-round" style="font-size:14px;">visibility</i></a>
              <a href="orcamento_form.php?cod=<?= $orc['cod'] ?>" class="btn-icone"><i class="material-icons-round" style="font-size:14px;">edit</i></a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ═══════════ ABA FINANCEIRO ═══════════ -->
  <?php elseif ($aba === 'financeiro'): ?>
  <div class="metricas mb-20">
    <div class="metrica"><div class="metrica-acento acento-verde"></div><div class="metrica-label"><i class="material-icons-round">trending_up</i> Receitas</div><div class="metrica-valor">€ <?= number_format($fin_tot['receitas']??0,0,',','.') ?></div><div class="metrica-sub">Total recebido</div></div>
    <div class="metrica"><div class="metrica-acento acento-laranja"></div><div class="metrica-label"><i class="material-icons-round">schedule</i> Pendente</div><div class="metrica-valor">€ <?= number_format($fin_tot['pendente']??0,0,',','.') ?></div><div class="metrica-sub">A receber</div></div>
    <div class="metrica"><div class="metrica-acento acento-vermelho"></div><div class="metrica-label"><i class="material-icons-round">warning</i> Vencido</div><div class="metrica-valor" style="color:<?= ($fin_tot['vencido']??0)>0?'var(--vermelho)':'var(--c900)' ?>">€ <?= number_format($fin_tot['vencido']??0,0,',','.') ?></div><div class="metrica-sub">Em atraso</div></div>
    <div class="metrica"><div class="metrica-acento acento-azul"></div><div class="metrica-label"><i class="material-icons-round">account_balance</i> Saldo</div><div class="metrica-valor" style="color:var(--verde);">€ <?= number_format(($fin_tot['receitas']??0)-($fin_tot['despesas']??0),0,',','.') ?></div><div class="metrica-sub">Receitas - Despesas</div></div>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-titulo">Lançamentos financeiros</span>
      <a href="financeiro_form.php?cliente_cod=<?= $cod ?>" class="btn btn-primario btn-sm">
        <i class="material-icons-round">add</i> Novo lançamento
      </a>
    </div>
    <?php if (mysqli_num_rows($lancamentos) === 0): ?>
    <div class="vazio">
      <i class="material-icons-round">account_balance_wallet</i>
      <h3>Sem lançamentos financeiros</h3>
      <p>Registe receitas e despesas associadas a este cliente.</p>
      <a href="financeiro_form.php?cliente_cod=<?= $cod ?>" class="btn btn-primario btn-sm"><i class="material-icons-round">add</i> Novo lançamento</a>
    </div>
    <?php else: ?>
    <div class="tabela-wrap">
      <table class="tabela">
        <thead><tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Tipo</th><th style="text-align:right;">Valor</th><th>Vencimento</th><th>Estado</th><th style="text-align:center;">Acções</th></tr></thead>
        <tbody>
        <?php while ($l = mysqli_fetch_assoc($lancamentos)):
          [$bf,$bfl] = $badge_fin[$l['status']]??['badge-cinza',ucfirst($l['status'])];
          $is_rec = $l['tipo']==='receita';
        ?>
        <tr>
          <td style="font-size:12px;color:var(--c500);"><?= date('d/m/Y',strtotime($l['data_lancamento'])) ?></td>
          <td><div class="td-nome"><?= htmlspecialchars(mb_strimwidth($l['descricao'],0,45,'…')) ?></div><?php if ($l['referencia']): ?><div class="td-sub">Ref: <?= htmlspecialchars($l['referencia']) ?></div><?php endif; ?></td>
          <td style="font-size:12.5px;color:var(--c600);"><?= $l['categoria']?htmlspecialchars($l['categoria']):'—' ?></td>
          <td><span class="badge <?= $is_rec?'badge-verde':'badge-vermelho' ?>" style="font-size:10.5px;"><?= $is_rec?'Receita':'Despesa' ?></span></td>
          <td style="text-align:right;font-weight:700;color:<?= $is_rec?'var(--verde)':'var(--vermelho)' ?>;"><?= $is_rec?'+':'-' ?> € <?= number_format($l['valor'],2,',','.') ?></td>
          <td style="font-size:12px;color:<?= ($l['data_vencimento']&&$l['data_vencimento']<date('Y-m-d')&&$l['status']==='pendente')?'var(--vermelho)':'var(--c500)' ?>;">
            <?= $l['data_vencimento']?date('d/m/Y',strtotime($l['data_vencimento'])):'—' ?>
          </td>
          <td><span class="badge <?= $bf ?>"><?= $bfl ?></span></td>
          <td>
            <div class="acoes" style="justify-content:center;">
              <a href="financeiro_form.php?cod=<?= $l['cod'] ?>&cliente_cod=<?= $cod ?>" class="btn-icone" title="Editar"><i class="material-icons-round" style="font-size:14px;">edit</i></a>
              <?php if ($l['status']==='pendente'): ?>
              <a href="financeiro_pagar.php?cod=<?= $l['cod'] ?>&cliente_cod=<?= $cod ?>"
                 class="btn-icone" title="Marcar como pago" style="color:var(--verde);"
                 onclick="return confirm('Marcar como pago?')">
                <i class="material-icons-round" style="font-size:14px;">check_circle</i>
              </a>
              <?php endif; ?>
              <a href="financeiro_excluir.php?cod=<?= $l['cod'] ?>&cliente_cod=<?= $cod ?>"
                 class="btn-icone perigo" title="Eliminar"
                 onclick="return confirm('Eliminar este lançamento?')">
                <i class="material-icons-round" style="font-size:14px;">delete</i>
              </a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Rodapé -->
  <div style="display:flex;justify-content:space-between;align-items:center;padding:20px 0;flex-wrap:wrap;gap:10px;">
    <a href="clientes.php" class="btn btn-outline">
      <i class="material-icons-round">arrow_back</i> Voltar à lista
    </a>
    <div style="display:flex;gap:8px;">
      <a href="clientes_excluir.php?cod=<?= $cod ?>"
         class="btn btn-perigo"
         onclick="return confirm('Eliminar este cliente e todos os dados associados?')">
        <i class="material-icons-round">delete</i> Eliminar
      </a>
      <a href="clientes_form.php?cod=<?= $cod ?>" class="btn btn-primario">
        <i class="material-icons-round">edit</i> Editar ficha
      </a>
    </div>
  </div>

</main>

<?php require_once("footer.php"); ?>
