<?php
require_once("db/conexao.php");

$cod = (int)($_GET['cod'] ?? 0);
if ($cod === 0) { header("Location: funcionarios.php"); exit; }

$r = mysqli_query($con, "SELECT * FROM funcionarios WHERE cod=$cod");
if (!$r || mysqli_num_rows($r) === 0) { header("Location: funcionarios.php"); exit; }
$f = mysqli_fetch_assoc($r);

// Equipamentos atribuídos a este colaborador (pelo nome)
$nome_esc = mysqli_real_escape_string($con, $f['nome']);
$equip = mysqli_query($con,
    "SELECT * FROM controle_equipamentos
     WHERE nome_funcionario LIKE '%$nome_esc%'
     ORDER BY data_cadastro DESC");

$msgs = [
    'criado'  => ['sucesso','Funcionário registado com sucesso!'],
    'editado' => ['sucesso','Dados actualizados com sucesso!'],
];
[$mt, $mm] = $msgs[$_GET['msg'] ?? ''] ?? ['',''];

$p  = explode(' ', trim($f['nome']));
$av = strtoupper(substr($p[0],0,1)).(count($p)>1?strtoupper(substr(end($p),0,1)):'');

$badge_status = [
    'ativo'   => ['badge-verde',   'Activo'],
    'inativo' => ['badge-cinza',   'Inactivo'],
    'ferias'  => ['badge-azul',    'Férias'],
    'baixa'   => ['badge-laranja', 'Baixa'],
];
[$badge, $blabel] = $badge_status[$f['status']] ?? ['badge-cinza', ucfirst($f['status'])];

$tipo_map = [
    'efetivo'           => 'Efectivo',
    'termo_certo'       => 'Contrato a termo certo',
    'termo_incerto'     => 'Contrato a termo incerto',
    'prestacao_servicos'=> 'Prestação de serviços',
    'estagio'           => 'Estágio',
    'outro'             => 'Outro',
];

require_once("header.php");

function info($label, $valor, $icon='') {
    $v  = $valor ? htmlspecialchars($valor) : '<span style="color:var(--c300);">Não indicado</span>';
    $ic = $icon  ? "<i class='material-icons-round' style='font-size:15px;color:var(--c400);flex-shrink:0;margin-top:1px;'>$icon</i>" : '';
    echo "<div style='padding:9px 0;border-bottom:1px solid var(--c100);display:flex;gap:10px;align-items:flex-start;'>
            $ic
            <div style='flex:1;'>
              <div style='font-size:10.5px;font-weight:700;color:var(--c400);text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px;'>$label</div>
              <div style='font-size:13.5px;color:var(--c800);'>$v</div>
            </div>
          </div>";
}
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
      <div style="width:68px;height:68px;border-radius:50%;background:var(--azul);
                  display:flex;align-items:center;justify-content:center;
                  font-size:24px;font-weight:700;color:#fff;flex-shrink:0;">
        <?= $av ?>
      </div>
      <div style="flex:1;min-width:200px;">
        <div style="font-size:20px;font-weight:700;color:var(--c900);margin-bottom:6px;">
          <?= htmlspecialchars($f['nome']) ?>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
          <?php if ($f['cargo']): ?>
          <span style="font-size:13px;color:var(--c600);font-weight:500;"><?= htmlspecialchars($f['cargo']) ?></span>
          <?php endif; ?>
          <?php if ($f['departamento']): ?>
          <span style="color:var(--c300);">·</span>
          <span style="font-size:13px;color:var(--c500);"><?= htmlspecialchars($f['departamento']) ?></span>
          <?php endif; ?>
          <span class="badge <?= $badge ?>"><?= $blabel ?></span>
        </div>
        <?php if ($f['data_admissao']): ?>
        <div style="font-size:12px;color:var(--c400);margin-top:6px;">
          <i class="material-icons-round" style="font-size:13px;vertical-align:-2px;">calendar_today</i>
          Admissão: <?= date('d/m/Y', strtotime($f['data_admissao'])) ?>
          <?php
          $diff = (new DateTime($f['data_admissao']))->diff(new DateTime());
          $anos = $diff->y;
          $meses= $diff->m;
          if ($anos > 0 || $meses > 0) {
              echo " · <strong>{$anos} ano(s) e {$meses} mês(es)</strong> de empresa";
          }
          ?>
        </div>
        <?php endif; ?>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php if ($f['telemovel']): ?>
        <a href="tel:<?= preg_replace('/\D/','',$f['telemovel']) ?>" class="btn btn-outline btn-sm">
          <i class="material-icons-round">phone</i> Ligar
        </a>
        <?php endif; ?>
        <?php if ($f['email']): ?>
        <a href="mailto:<?= htmlspecialchars($f['email']) ?>" class="btn btn-outline btn-sm">
          <i class="material-icons-round">email</i> E-mail
        </a>
        <?php endif; ?>
        <a href="funcionarios_form.php?cod=<?= $f['cod'] ?>" class="btn btn-primario btn-sm">
          <i class="material-icons-round">edit</i> Editar
        </a>
      </div>
    </div>
  </div>

  <!-- Grelha de detalhes -->
  <div class="grid-2 mb-16">

    <!-- Identificação -->
    <div class="card">
      <div class="card-header">
        <span class="card-titulo"><i class="material-icons-round" style="font-size:16px;vertical-align:-3px;">badge</i> Identificação</span>
      </div>
      <div class="card-body" style="padding-top:0;padding-bottom:0;">
        <?php info('NIF', $f['nif'], 'fingerprint') ?>
        <?php info('Cartão de Cidadão', $f['cc'], 'credit_card') ?>
        <?php info('Data de nascimento', $f['data_nascimento']?date('d/m/Y',strtotime($f['data_nascimento'])):'', 'cake') ?>
        <?php info('IBAN', $f['iban'], 'account_balance') ?>
      </div>
    </div>

    <!-- Dados profissionais -->
    <div class="card">
      <div class="card-header">
        <span class="card-titulo"><i class="material-icons-round" style="font-size:16px;vertical-align:-3px;">work</i> Dados profissionais</span>
      </div>
      <div class="card-body" style="padding-top:0;padding-bottom:0;">
        <?php info('Cargo / Função', $f['cargo'], 'work') ?>
        <?php info('Departamento', $f['departamento'], 'corporate_fare') ?>
        <?php info('Tipo de contrato', $tipo_map[$f['tipo_contrato']] ?? $f['tipo_contrato'], 'description') ?>
        <?php info('Salário bruto', $f['salario']?'€ '.number_format((float)$f['salario'],2,',','.'):'', 'euro') ?>
        <?php info('Data de admissão', $f['data_admissao']?date('d/m/Y',strtotime($f['data_admissao'])):'', 'event') ?>
        <?php if ($f['data_saida']): ?>
        <?php info('Data de saída', date('d/m/Y',strtotime($f['data_saida'])), 'event_busy') ?>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- Contactos e morada -->
  <div class="grid-2 mb-16">

    <div class="card">
      <div class="card-header">
        <span class="card-titulo"><i class="material-icons-round" style="font-size:16px;vertical-align:-3px;">contact_phone</i> Contactos</span>
      </div>
      <div class="card-body" style="padding-top:0;padding-bottom:0;">
        <?php info('E-mail', $f['email'], 'email') ?>
        <?php info('Telefone fixo', $f['telefone'], 'phone') ?>
        <?php info('Telemóvel', $f['telemovel'], 'smartphone') ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <span class="card-titulo"><i class="material-icons-round" style="font-size:16px;vertical-align:-3px;">home</i> Morada</span>
      </div>
      <div class="card-body" style="padding-top:0;padding-bottom:0;">
        <?php info('Código postal', $f['cep']) ?>
        <?php info('Rua', $f['endereco'].($f['numero']?', Nº '.$f['numero']:'')) ?>
        <?php info('Localidade', $f['bairro']) ?>
        <?php info('Concelho', $f['cidade']) ?>
        <?php info('Distrito', $f['estado']) ?>
      </div>
    </div>

  </div>

  <!-- Equipamentos atribuídos -->
  <div class="card mb-16">
    <div class="card-header">
      <span class="card-titulo">
        <i class="material-icons-round" style="font-size:16px;vertical-align:-3px;">computer</i>
        Equipamentos atribuídos
      </span>
      <a href="adicionar_equipamento.php" class="btn btn-outline btn-sm">
        <i class="material-icons-round">add</i> Novo
      </a>
    </div>
    <?php if (mysqli_num_rows($equip) > 0): ?>
    <div class="tabela-wrap">
      <table class="tabela">
        <thead><tr><th>Equipamento</th><th>Fabricante</th><th>Modelo</th><th>S/N</th><th>Registado em</th></tr></thead>
        <tbody>
        <?php while ($eq = mysqli_fetch_assoc($equip)): ?>
        <tr>
          <td><div class="td-nome"><?= htmlspecialchars($eq['equipamento']) ?></div></td>
          <td style="color:var(--c600);"><?= htmlspecialchars($eq['fabricante']?:'—') ?></td>
          <td style="color:var(--c600);"><?= htmlspecialchars($eq['modelo']?:'—') ?></td>
          <td style="font-family:monospace;font-size:12px;color:var(--c500);"><?= htmlspecialchars($eq['sn']?:'—') ?></td>
          <td style="font-size:12px;color:var(--c500);"><?= date('d/m/Y',strtotime($eq['data_cadastro'])) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="vazio" style="padding:24px;">
      <i class="material-icons-round">computer</i>
      <h3>Sem equipamentos atribuídos</h3>
    </div>
    <?php endif; ?>
  </div>

  <!-- Observações -->
  <?php if ($f['observacoes']): ?>
  <div class="card mb-16">
    <div class="card-header"><span class="card-titulo">Observações</span></div>
    <div class="card-body">
      <p style="font-size:13.5px;color:var(--c700);line-height:1.7;white-space:pre-wrap;"><?= htmlspecialchars($f['observacoes']) ?></p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Rodapé -->
  <div style="display:flex;justify-content:space-between;align-items:center;padding-bottom:24px;flex-wrap:wrap;gap:10px;">
    <a href="funcionarios.php" class="btn btn-outline">
      <i class="material-icons-round">arrow_back</i> Voltar à lista
    </a>
    <div style="display:flex;gap:8px;">
      <a href="funcionarios_excluir.php?cod=<?= $f['cod'] ?>"
         class="btn btn-perigo"
         onclick="return confirm('Eliminar este funcionário? Esta acção não pode ser revertida.')">
        <i class="material-icons-round">delete</i> Eliminar
      </a>
      <a href="funcionarios_form.php?cod=<?= $f['cod'] ?>" class="btn btn-primario">
        <i class="material-icons-round">edit</i> Editar ficha
      </a>
    </div>
  </div>

</main>

<?php require_once("footer.php"); ?>
