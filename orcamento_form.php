<?php
require_once("db/conexao.php");

$cod    = (int)($_GET['cod'] ?? 0);
$editar = $cod > 0;
$orc    = [];
$linhas = [];
$erros  = [];

if ($editar) {
    $r = mysqli_query($con, "SELECT * FROM orcamentos WHERE cod=$cod");
    if (!$r || mysqli_num_rows($r) === 0) { header("Location: orcamentos.php?msg=erro"); exit; }
    $orc = mysqli_fetch_assoc($r);
    $rl  = mysqli_query($con, "SELECT * FROM orcamento_linhas WHERE orcamento_cod=$cod ORDER BY ordem");
    while ($l = mysqli_fetch_assoc($rl)) $linhas[] = $l;
}

// Gera número automático
function gerarNumero($con) {
    $ano = date('Y');
    $r   = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT COUNT(*) AS t FROM orcamentos WHERE YEAR(criado_em)=$ano"));
    $seq = str_pad(($r['t'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);
    return "ORC-$ano-$seq";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_cod   = (int)($_POST['cliente_cod'] ?? 0);
    $cliente_nome  = trim($_POST['cliente_nome']  ?? '');
    $cliente_email = trim($_POST['cliente_email'] ?? '');
    $cliente_telef = trim($_POST['cliente_telef'] ?? '');
    $titulo        = trim($_POST['titulo']        ?? '');
    $descricao     = trim($_POST['descricao']     ?? '');
    $estado_cod    = (int)($_POST['estado_cod']   ?? 1);
    $validade      = $_POST['validade']           ?? '';
    $data_emissao  = $_POST['data_emissao']       ?? date('Y-m-d');
    $iva_pct       = (float)($_POST['iva_pct']    ?? 23);
    $desconto_pct  = (float)($_POST['desconto_pct'] ?? 0);
    $notas         = trim($_POST['notas']         ?? '');
    $termos        = trim($_POST['termos']        ?? '');
    $numero        = trim($_POST['numero']        ?? '');
    $uc            = (int)$_SESSION['cod'];

    // Linhas
    $descs   = $_POST['linha_desc']    ?? [];
    $qtds    = $_POST['linha_qtd']     ?? [];
    $units   = $_POST['linha_unit']    ?? [];
    $precos  = $_POST['linha_preco']   ?? [];
    $descs_p = $_POST['linha_desc_pct']?? [];

    if ($titulo === '')        $erros[] = 'O título é obrigatório.';
    if ($cliente_nome === '')  $erros[] = 'O nome do cliente é obrigatório.';
    if (empty($descs) || array_filter($descs) === []) $erros[] = 'Adicione pelo menos uma linha ao orçamento.';

    if (empty($erros)) {
        // Calcula totais
        $subtotal = 0;
        $linhas_calc = [];
        foreach ($descs as $i => $desc) {
            if (trim($desc) === '') continue;
            $qtd   = (float)($qtds[$i]  ?? 1);
            $preco = (float)($precos[$i]?? 0);
            $dpct  = (float)($descs_p[$i]??0);
            $total_linha = $qtd * $preco * (1 - $dpct/100);
            $subtotal += $total_linha;
            $linhas_calc[] = [
                'descricao'    => $desc,
                'quantidade'   => $qtd,
                'unidade'      => $units[$i] ?? 'un',
                'preco_unit'   => $preco,
                'desconto_pct' => $dpct,
                'total_linha'  => $total_linha,
                'ordem'        => $i + 1,
            ];
        }
        $desconto_valor = $subtotal * ($desconto_pct / 100);
        $base_iva       = $subtotal - $desconto_valor;
        $iva_valor      = $base_iva * ($iva_pct / 100);
        $total          = $base_iva + $iva_valor;

        // Escapa
        $esc = fn($v) => mysqli_real_escape_string($con, $v);
        $num = $editar ? $esc($numero) : $esc(gerarNumero($con));

        if ($editar) {
            mysqli_query($con, "UPDATE orcamentos SET
                numero='$num', cliente_cod=".($cliente_cod?:0).",
                cliente_nome='".$esc($cliente_nome)."',
                cliente_email='".$esc($cliente_email)."',
                cliente_telef='".$esc($cliente_telef)."',
                titulo='".$esc($titulo)."', descricao='".$esc($descricao)."',
                estado_cod=$estado_cod,
                validade=".($validade?"'".$esc($validade)."'":"NULL").",
                data_emissao='".$esc($data_emissao)."',
                subtotal=$subtotal, desconto_pct=$desconto_pct,
                desconto_valor=$desconto_valor, iva_pct=$iva_pct,
                iva_valor=$iva_valor, total=$total,
                notas='".$esc($notas)."', termos='".$esc($termos)."'
                WHERE cod=$cod");
            mysqli_query($con, "DELETE FROM orcamento_linhas WHERE orcamento_cod=$cod");
        } else {
            mysqli_query($con, "INSERT INTO orcamentos
                (numero,cliente_cod,cliente_nome,cliente_email,cliente_telef,titulo,descricao,
                 estado_cod,validade,data_emissao,subtotal,desconto_pct,desconto_valor,
                 iva_pct,iva_valor,total,notas,termos,usuario_cod)
                VALUES ('$num',".($cliente_cod?:0).",'".$esc($cliente_nome)."',
                '".$esc($cliente_email)."','".$esc($cliente_telef)."',
                '".$esc($titulo)."','".$esc($descricao)."',
                $estado_cod,".($validade?"'".$esc($validade)."'":"NULL").",
                '".$esc($data_emissao)."',$subtotal,$desconto_pct,$desconto_valor,
                $iva_pct,$iva_valor,$total,'".$esc($notas)."','".$esc($termos)."',$uc)");
            $cod = mysqli_insert_id($con);
        }

        // Insere linhas
        foreach ($linhas_calc as $l) {
            mysqli_query($con, "INSERT INTO orcamento_linhas
                (orcamento_cod,ordem,descricao,quantidade,unidade,preco_unit,desconto_pct,total_linha)
                VALUES ($cod,{$l['ordem']},'".$esc($l['descricao'])."',
                {$l['quantidade']},'".$esc($l['unidade'])."',
                {$l['preco_unit']},{$l['desconto_pct']},{$l['total_linha']})");
        }
        header("Location: orcamento_ver.php?cod=$cod&msg=".($editar?'editado':'criado')); exit;
    }
}

$clientes_lista = mysqli_query($con, "SELECT cod, nome_completo, email, celular FROM clientes WHERE status='ativo' ORDER BY nome_completo");
$estados_lista  = mysqli_query($con, "SELECT * FROM orcamento_estados ORDER BY cod");

$orc['estado_cod']   = $orc['estado_cod']   ?? 1;
$orc['iva_pct']      = $orc['iva_pct']      ?? 23;
$orc['data_emissao'] = $orc['data_emissao'] ?? date('Y-m-d');
$orc['numero']       = $orc['numero']       ?? gerarNumero($con);

require_once("header.php");
function v($a,$k){ return htmlspecialchars($a[$k] ?? ''); }
function s($a,$k,$v){ return (string)($a[$k]??'') === (string)$v ? 'selected' : ''; }
?>

<main class="pagina">

  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="orcamentos.php" class="btn-icone"><i class="material-icons-round">arrow_back</i></a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo"><?= $editar ? 'Editar orçamento' : 'Novo orçamento' ?></h1>
        <p class="pagina-subtitulo"><?= $editar ? 'Actualizar proposta comercial' : 'Criar nova proposta comercial' ?></p>
      </div>
    </div>
  </div>

  <?php if (!empty($erros)): ?>
  <div class="alerta alerta-erro mb-20">
    <i class="material-icons-round">error_outline</i>
    <div><strong>Corrija os erros:</strong>
      <ul style="margin:4px 0 0 16px;padding:0;">
        <?php foreach ($erros as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>

  <form method="POST" id="form-orc">
  <div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start;">

    <div>
      <!-- Cabeçalho do orçamento -->
      <div class="card mb-16">
        <div class="card-header"><span class="card-titulo">Dados do orçamento</span></div>
        <div class="card-body">
          <div class="form-grid form-grid-2">
            <div class="form-grupo">
              <label class="form-label">Nº Orçamento</label>
              <input type="text" name="numero" class="form-input" value="<?= v($orc,'numero') ?>" readonly
                     style="background:var(--c50);font-family:monospace;font-weight:600;color:var(--azul);">
            </div>
            <div class="form-grupo">
              <label class="form-label">Estado</label>
              <select name="estado_cod" class="form-select">
                <?php while ($est = mysqli_fetch_assoc($estados_lista)): ?>
                <option value="<?= $est['cod'] ?>" <?= s($orc,'estado_cod',$est['cod']) ?>>
                  <?= htmlspecialchars($est['nome']) ?>
                </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-grupo col-span-full">
              <label class="form-label">Título / Assunto <span class="obg">*</span></label>
              <input type="text" name="titulo" class="form-input"
                     placeholder="Ex: Proposta de serviços de TI — Empresa X"
                     value="<?= v($orc,'titulo') ?>" required>
            </div>
            <div class="form-grupo">
              <label class="form-label">Data de emissão</label>
              <input type="date" name="data_emissao" class="form-input" value="<?= v($orc,'data_emissao') ?>">
            </div>
            <div class="form-grupo">
              <label class="form-label">Válido até</label>
              <input type="date" name="validade" class="form-input" value="<?= v($orc,'validade') ?>">
            </div>
            <div class="form-grupo col-span-full">
              <label class="form-label">Descrição / Introdução</label>
              <textarea name="descricao" class="form-textarea" rows="2"
                        placeholder="Breve descrição da proposta…"><?= v($orc,'descricao') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Cliente -->
      <div class="card mb-16">
        <div class="card-header">
          <span class="card-titulo">Cliente</span>
          <span style="font-size:12px;color:var(--c400);">Seleccione ou preencha manualmente</span>
        </div>
        <div class="card-body">
          <div class="form-grupo">
            <label class="form-label">Seleccionar cliente existente</label>
            <select id="sel-cliente" class="form-select">
              <option value="">— Seleccionar da lista —</option>
              <?php while ($cl = mysqli_fetch_assoc($clientes_lista)): ?>
              <option value="<?= $cl['cod'] ?>"
                      data-nome="<?= htmlspecialchars($cl['nome_completo']) ?>"
                      data-email="<?= htmlspecialchars($cl['email']) ?>"
                      data-telef="<?= htmlspecialchars($cl['celular']) ?>"
                      <?= (int)($orc['cliente_cod']??0)===(int)$cl['cod']?'selected':'' ?>>
                <?= htmlspecialchars($cl['nome_completo']) ?>
              </option>
              <?php endwhile; ?>
            </select>
            <input type="hidden" name="cliente_cod" id="cliente_cod" value="<?= v($orc,'cliente_cod') ?>">
          </div>
          <div class="form-grid form-grid-2">
            <div class="form-grupo">
              <label class="form-label">Nome <span class="obg">*</span></label>
              <input type="text" name="cliente_nome" id="cliente_nome" class="form-input"
                     placeholder="Nome completo ou empresa" value="<?= v($orc,'cliente_nome') ?>" required>
            </div>
            <div class="form-grupo">
              <label class="form-label">E-mail</label>
              <input type="email" name="cliente_email" id="cliente_email" class="form-input"
                     placeholder="email@exemplo.com" value="<?= v($orc,'cliente_email') ?>">
            </div>
            <div class="form-grupo" style="margin-bottom:0;">
              <label class="form-label">Telefone</label>
              <input type="text" name="cliente_telef" id="cliente_telef" class="form-input"
                     placeholder="+351 9xx xxx xxx" value="<?= v($orc,'cliente_telef') ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Linhas do orçamento -->
      <div class="card mb-16">
        <div class="card-header">
          <span class="card-titulo">Itens / Serviços</span>
          <button type="button" id="btn-add-linha" class="btn btn-outline btn-sm">
            <i class="material-icons-round">add</i> Adicionar linha
          </button>
        </div>
        <div class="card-body" style="padding:0;">

          <!-- Cabeçalhos das colunas -->
          <div style="display:grid;grid-template-columns:1fr 70px 70px 100px 70px 100px 36px;gap:8px;padding:10px 18px;background:var(--c50);border-bottom:1px solid var(--c200);">
            <div style="font-size:11px;font-weight:600;color:var(--c500);text-transform:uppercase;letter-spacing:.05em;">Descrição</div>
            <div style="font-size:11px;font-weight:600;color:var(--c500);text-transform:uppercase;letter-spacing:.05em;">Qtd.</div>
            <div style="font-size:11px;font-weight:600;color:var(--c500);text-transform:uppercase;letter-spacing:.05em;">Un.</div>
            <div style="font-size:11px;font-weight:600;color:var(--c500);text-transform:uppercase;letter-spacing:.05em;">Preço unit.</div>
            <div style="font-size:11px;font-weight:600;color:var(--c500);text-transform:uppercase;letter-spacing:.05em;">Desc. %</div>
            <div style="font-size:11px;font-weight:600;color:var(--c500);text-transform:uppercase;letter-spacing:.05em;text-align:right;">Total</div>
            <div></div>
          </div>

          <div id="linhas-container" style="padding:0 18px;">
            <?php
            if (empty($linhas)) $linhas = [['descricao'=>'','quantidade'=>1,'unidade'=>'un','preco_unit'=>0,'desconto_pct'=>0,'total_linha'=>0]];
            foreach ($linhas as $i => $l):
            ?>
            <div class="linha-orc" data-linha>
              <div><input type="text" name="linha_desc[]" class="form-input" placeholder="Descrição do serviço ou produto" value="<?= htmlspecialchars($l['descricao']) ?>"></div>
              <div><input type="number" name="linha_qtd[]"  class="form-input linha-calc" step="0.001" min="0" value="<?= $l['quantidade'] ?>" placeholder="1"></div>
              <div><input type="text"   name="linha_unit[]" class="form-input" value="<?= htmlspecialchars($l['unidade']) ?>" placeholder="un"></div>
              <div><input type="number" name="linha_preco[]" class="form-input linha-calc" step="0.01" min="0" value="<?= $l['preco_unit'] ?>" placeholder="0,00"></div>
              <div><input type="number" name="linha_desc_pct[]" class="form-input linha-calc" step="0.01" min="0" max="100" value="<?= $l['desconto_pct'] ?>" placeholder="0"></div>
              <div><input type="text" class="form-input linha-total" readonly style="text-align:right;background:var(--c50);font-weight:600;" value="€ <?= number_format($l['total_linha'],2,',','.') ?>"></div>
              <div><button type="button" class="btn-icone perigo btn-rem-linha" title="Remover"><i class="material-icons-round" style="font-size:14px;">delete</i></button></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Notas e Termos -->
      <div class="card mb-16">
        <div class="card-header"><span class="card-titulo">Notas e condições</span></div>
        <div class="card-body">
          <div class="form-grid form-grid-2">
            <div class="form-grupo" style="margin-bottom:0;">
              <label class="form-label">Notas / Observações</label>
              <textarea name="notas" class="form-textarea" rows="3"
                        placeholder="Informações adicionais para o cliente…"><?= v($orc,'notas') ?></textarea>
            </div>
            <div class="form-grupo" style="margin-bottom:0;">
              <label class="form-label">Termos e condições</label>
              <textarea name="termos" class="form-textarea" rows="3"
                        placeholder="Condições de pagamento, prazo de entrega…"><?= v($orc,'termos') ?></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Painel lateral de totais -->
    <div style="position:sticky;top:calc(var(--topbar-h) + 16px);">
      <div class="orc-totais mb-16">
        <div style="font-size:13px;font-weight:600;color:var(--c800);margin-bottom:12px;">Resumo de valores</div>

        <div class="form-grupo">
          <label class="form-label">IVA (%)</label>
          <select name="iva_pct" id="iva_pct" class="form-select">
            <option value="23" <?= s($orc,'iva_pct','23') ?>>23% (normal)</option>
            <option value="13" <?= s($orc,'iva_pct','13') ?>>13% (intermédio)</option>
            <option value="6"  <?= s($orc,'iva_pct','6')  ?>>6% (reduzido)</option>
            <option value="0"  <?= s($orc,'iva_pct','0')  ?>>0% (isento)</option>
          </select>
        </div>
        <div class="form-grupo">
          <label class="form-label">Desconto global (%)</label>
          <input type="number" name="desconto_pct" id="desconto_pct" class="form-input"
                 step="0.01" min="0" max="100" value="<?= v($orc,'desconto_pct') ?>" placeholder="0">
        </div>

        <div style="height:1px;background:var(--c200);margin:12px 0;"></div>

        <div class="orc-totais-linha">
          <span>Subtotal</span>
          <span id="tot-subtotal">€ 0,00</span>
        </div>
        <div class="orc-totais-linha" id="row-desconto" style="<?= !($orc['desconto_pct']??0)?'color:var(--c400)':'' ?>">
          <span>Desconto</span>
          <span id="tot-desconto">— € 0,00</span>
        </div>
        <div class="orc-totais-linha">
          <span>Base tributável</span>
          <span id="tot-base">€ 0,00</span>
        </div>
        <div class="orc-totais-linha">
          <span>IVA (<span id="lbl-iva">23</span>%)</span>
          <span id="tot-iva">€ 0,00</span>
        </div>
        <div class="orc-totais-linha" style="border-top:2px solid var(--c200);margin-top:4px;">
          <span>Total</span>
          <span id="tot-total" style="color:var(--azul);">€ 0,00</span>
        </div>
      </div>

      <button type="submit" class="btn btn-primario" style="width:100%;justify-content:center;padding:11px;">
        <i class="material-icons-round"><?= $editar?'save':'send' ?></i>
        <?= $editar ? 'Guardar alterações' : 'Criar orçamento' ?>
      </button>
      <a href="orcamentos.php" class="btn btn-outline" style="width:100%;justify-content:center;margin-top:8px;">Cancelar</a>
    </div>

  </div>
  </form>
</main>

<script>
// ── Preenche cliente ao seleccionar ────────────────────────
document.getElementById('sel-cliente').addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  document.getElementById('cliente_cod').value   = opt.value || '';
  document.getElementById('cliente_nome').value  = opt.dataset.nome  || '';
  document.getElementById('cliente_email').value = opt.dataset.email || '';
  document.getElementById('cliente_telef').value = opt.dataset.telef || '';
});

// ── Cálculo de totais ───────────────────────────────────────
function fmt(v) { return '€ ' + v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }

function calcular() {
  let subtotal = 0;
  document.querySelectorAll('[data-linha]').forEach(row => {
    const qtd   = parseFloat(row.querySelector('[name="linha_qtd[]"]').value)  || 0;
    const preco = parseFloat(row.querySelector('[name="linha_preco[]"]').value) || 0;
    const dpct  = parseFloat(row.querySelector('[name="linha_desc_pct[]"]').value) || 0;
    const total = qtd * preco * (1 - dpct / 100);
    row.querySelector('.linha-total').value = fmt(total);
    subtotal += total;
  });

  const desc_pct   = parseFloat(document.getElementById('desconto_pct').value) || 0;
  const iva_pct    = parseFloat(document.getElementById('iva_pct').value) || 0;
  const desc_valor = subtotal * (desc_pct / 100);
  const base       = subtotal - desc_valor;
  const iva_valor  = base * (iva_pct / 100);
  const total      = base + iva_valor;

  document.getElementById('tot-subtotal').textContent = fmt(subtotal);
  document.getElementById('tot-desconto').textContent = '— ' + fmt(desc_valor);
  document.getElementById('tot-base').textContent     = fmt(base);
  document.getElementById('tot-iva').textContent      = fmt(iva_valor);
  document.getElementById('tot-total').textContent    = fmt(total);
  document.getElementById('lbl-iva').textContent      = iva_pct;
}

document.addEventListener('input', e => {
  if (e.target.closest('[data-linha]') || e.target.id === 'desconto_pct' || e.target.id === 'iva_pct') calcular();
});
document.getElementById('iva_pct').addEventListener('change', calcular);

// ── Adicionar linha ─────────────────────────────────────────
document.getElementById('btn-add-linha').addEventListener('click', () => {
  const cont = document.getElementById('linhas-container');
  const div  = document.createElement('div');
  div.className = 'linha-orc';
  div.setAttribute('data-linha','');
  div.innerHTML = `
    <div><input type="text"   name="linha_desc[]"     class="form-input" placeholder="Descrição"></div>
    <div><input type="number" name="linha_qtd[]"      class="form-input linha-calc" value="1" min="0" step="0.001"></div>
    <div><input type="text"   name="linha_unit[]"     class="form-input" value="un"></div>
    <div><input type="number" name="linha_preco[]"    class="form-input linha-calc" value="0" min="0" step="0.01"></div>
    <div><input type="number" name="linha_desc_pct[]" class="form-input linha-calc" value="0" min="0" max="100" step="0.01"></div>
    <div><input type="text"   class="form-input linha-total" readonly style="text-align:right;background:var(--c50);font-weight:600;" value="€ 0,00"></div>
    <div><button type="button" class="btn-icone perigo btn-rem-linha" title="Remover"><i class="material-icons-round" style="font-size:14px;">delete</i></button></div>`;
  cont.appendChild(div);
  div.querySelector('input').focus();
});

// ── Remover linha ───────────────────────────────────────────
document.addEventListener('click', e => {
  if (e.target.closest('.btn-rem-linha')) {
    const linhas = document.querySelectorAll('[data-linha]');
    if (linhas.length > 1) { e.target.closest('[data-linha]').remove(); calcular(); }
  }
});

calcular();
</script>

<?php require_once("footer.php"); ?>
