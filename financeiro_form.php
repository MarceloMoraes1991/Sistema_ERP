<?php
require_once("db/conexao.php");

$cod         = (int)($_GET['cod']         ?? 0);
$cliente_cod = (int)($_GET['cliente_cod'] ?? 0);
$editar      = $cod > 0;
$l           = [];
$erros       = [];

if ($editar) {
    $r = mysqli_query($con,"SELECT * FROM financeiro WHERE cod=$cod");
    if (!$r || mysqli_num_rows($r)===0) { header("Location: clientes.php"); exit; }
    $l = mysqli_fetch_assoc($r);
    $cliente_cod = $cliente_cod ?: (int)$l['cliente_cod'];
}

if ($cliente_cod === 0) { header("Location: clientes.php"); exit; }
$rc = mysqli_query($con,"SELECT * FROM clientes WHERE cod=$cliente_cod");
if (!$rc || mysqli_num_rows($rc)===0) { header("Location: clientes.php"); exit; }
$cliente = mysqli_fetch_assoc($rc);

// OS do cliente para associar
$os_lista = mysqli_query($con,"SELECT cod,numero,titulo FROM ordens_servico WHERE cliente_cod=$cliente_cod ORDER BY data_abertura DESC");

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $campos = ['tipo','categoria','descricao','valor','data_lancamento',
               'data_vencimento','data_pagamento','status','metodo_pag','referencia','notas'];
    foreach ($campos as $c) $l[$c] = trim($_POST[$c]??'');
    $os_cod = (int)($_POST['os_cod']??0);

    if ($l['descricao']==='') $erros[]='A descrição é obrigatória.';
    if (!is_numeric(str_replace(',','.',$l['valor']))) $erros[]='Valor inválido.';

    if (empty($erros)) {
        $valor = (float)str_replace(',','.',$l['valor']);
        $uc    = (int)$_SESSION['cod'];

        $v=[];
        foreach (['tipo','categoria','descricao','status','metodo_pag','referencia','notas'] as $c)
            $v[$c]=mysqli_real_escape_string($con,$l[$c]);

        $dl  = $l['data_lancamento'] ? "'{$l['data_lancamento']}'" : "CURDATE()";
        $dv  = $l['data_vencimento']  ? "'{$l['data_vencimento']}'"  : "NULL";
        $dp  = $l['data_pagamento']   ? "'{$l['data_pagamento']}'"   : "NULL";
        $osc = $os_cod > 0 ? $os_cod : "NULL";

        if ($editar) {
            mysqli_query($con,"UPDATE financeiro SET
                tipo='{$v['tipo']}', categoria='{$v['categoria']}',
                descricao='{$v['descricao']}', valor=$valor,
                data_lancamento=$dl, data_vencimento=$dv, data_pagamento=$dp,
                status='{$v['status']}', metodo_pag='{$v['metodo_pag']}',
                referencia='{$v['referencia']}', notas='{$v['notas']}',
                os_cod=$osc WHERE cod=$cod");
        } else {
            mysqli_query($con,"INSERT INTO financeiro
                (cliente_cod,os_cod,tipo,categoria,descricao,valor,
                 data_lancamento,data_vencimento,data_pagamento,
                 status,metodo_pag,referencia,notas,usuario_cod)
                VALUES ($cliente_cod,$osc,'{$v['tipo']}','{$v['categoria']}',
                '{$v['descricao']}',$valor,$dl,$dv,$dp,
                '{$v['status']}','{$v['metodo_pag']}','{$v['referencia']}',
                '{$v['notas']}',$uc)");
        }
        header("Location: clientes_detalhe.php?cod=$cliente_cod&aba=financeiro&msg=fin_ok"); exit;
    }
}

$l['tipo']            = $l['tipo']            ?? 'receita';
$l['status']          = $l['status']          ?? 'pendente';
$l['data_lancamento'] = $l['data_lancamento'] ?? date('Y-m-d');

require_once("header.php");
function v($l,$k){ return htmlspecialchars($l[$k]??''); }
function s($l,$k,$v){ return ($l[$k]??'')===$v?'selected':''; }
?>

<main class="pagina" style="max-width:720px;">
  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="clientes_detalhe.php?cod=<?= $cliente_cod ?>&aba=financeiro" class="btn-icone">
        <i class="material-icons-round">arrow_back</i>
      </a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo"><?= $editar?'Editar lançamento':'Novo lançamento' ?></h1>
        <p class="pagina-subtitulo">Cliente: <strong><?= htmlspecialchars($cliente['nome_completo']) ?></strong></p>
      </div>
    </div>
  </div>

  <?php if (!empty($erros)): ?>
  <div class="alerta alerta-erro mb-20">
    <i class="material-icons-round">error_outline</i>
    <div><?php foreach ($erros as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
  </div>
  <?php endif; ?>

  <form method="POST">
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Dados do lançamento</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">

          <!-- Tipo: Receita / Despesa -->
          <div class="form-grupo col-span-full">
            <label class="form-label">Tipo</label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
              <label style="cursor:pointer;">
                <input type="radio" name="tipo" value="receita" class="radio-tipo" style="display:none;" <?= ($l['tipo']??'receita')==='receita'?'checked':'' ?>>
                <div class="tipo-opt" data-val="receita"
                     style="border:2px solid var(--c200);border-radius:var(--r-md);padding:12px 16px;
                            display:flex;align-items:center;gap:10px;transition:all .15s;">
                  <i class="material-icons-round" style="color:var(--verde);font-size:22px;">trending_up</i>
                  <div><div style="font-weight:600;color:var(--c800);">Receita</div><div style="font-size:12px;color:var(--c500);">Valor a receber do cliente</div></div>
                </div>
              </label>
              <label style="cursor:pointer;">
                <input type="radio" name="tipo" value="despesa" class="radio-tipo" style="display:none;" <?= ($l['tipo']??'')==='despesa'?'checked':'' ?>>
                <div class="tipo-opt" data-val="despesa"
                     style="border:2px solid var(--c200);border-radius:var(--r-md);padding:12px 16px;
                            display:flex;align-items:center;gap:10px;transition:all .15s;">
                  <i class="material-icons-round" style="color:var(--vermelho);font-size:22px;">trending_down</i>
                  <div><div style="font-weight:600;color:var(--c800);">Despesa</div><div style="font-size:12px;color:var(--c500);">Custo associado ao cliente</div></div>
                </div>
              </label>
            </div>
          </div>

          <div class="form-grupo col-span-full">
            <label class="form-label">Descrição <span class="obg">*</span></label>
            <input type="text" name="descricao" class="form-input"
                   placeholder="Ex: Serviço de suporte técnico, Mensalidade, Material…"
                   value="<?= v($l,'descricao') ?>" required>
          </div>
          <div class="form-grupo">
            <label class="form-label">Categoria</label>
            <input type="text" name="categoria" class="form-input"
                   placeholder="Ex: Serviço, Material, Mensalidade"
                   value="<?= v($l,'categoria') ?>" list="cats">
            <datalist id="cats">
              <option value="Serviço técnico"><option value="Material / Peças">
              <option value="Mensalidade"><option value="Deslocação">
              <option value="Software / Licença"><option value="Consultoria">
            </datalist>
          </div>
          <div class="form-grupo">
            <label class="form-label">Associar a OS</label>
            <select name="os_cod" class="form-select">
              <option value="0">— Nenhuma —</option>
              <?php while ($os = mysqli_fetch_assoc($os_lista)): ?>
              <option value="<?= $os['cod'] ?>" <?= (int)($l['os_cod']??0)===(int)$os['cod']?'selected':'' ?>>
                <?= htmlspecialchars($os['numero'].' — '.mb_strimwidth($os['titulo'],0,30,'…')) ?>
              </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-grupo">
            <label class="form-label">Valor (€) <span class="obg">*</span></label>
            <input type="number" name="valor" class="form-input"
                   step="0.01" min="0" placeholder="0,00"
                   value="<?= v($l,'valor') ?>" required>
          </div>
          <div class="form-grupo">
            <label class="form-label">Estado</label>
            <select name="status" class="form-select">
              <option value="pendente"  <?= s($l,'status','pendente')  ?>>🟠 Pendente</option>
              <option value="pago"      <?= s($l,'status','pago')      ?>>🟢 Pago</option>
              <option value="vencido"   <?= s($l,'status','vencido')   ?>>🔴 Vencido</option>
              <option value="cancelado" <?= s($l,'status','cancelado') ?>>⚫ Cancelado</option>
            </select>
          </div>
          <div class="form-grupo">
            <label class="form-label">Data do lançamento</label>
            <input type="date" name="data_lancamento" class="form-input" value="<?= v($l,'data_lancamento') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Data de vencimento</label>
            <input type="date" name="data_vencimento" class="form-input" value="<?= v($l,'data_vencimento') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Data de pagamento</label>
            <input type="date" name="data_pagamento" class="form-input" value="<?= v($l,'data_pagamento') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Método de pagamento</label>
            <input type="text" name="metodo_pag" class="form-input"
                   placeholder="Ex: Transferência, MB Way, Numerário"
                   value="<?= v($l,'metodo_pag') ?>" list="metodos">
            <datalist id="metodos">
              <option value="Transferência bancária"><option value="MB Way">
              <option value="Numerário"><option value="Cheque">
              <option value="MB (Referência)"><option value="Débito directo">
            </datalist>
          </div>
          <div class="form-grupo">
            <label class="form-label">Referência / Nº documento</label>
            <input type="text" name="referencia" class="form-input"
                   placeholder="Nº factura, referência MB…"
                   value="<?= v($l,'referencia') ?>">
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Notas</label>
            <textarea name="notas" class="form-textarea" rows="2"
                      placeholder="Notas adicionais…"><?= v($l,'notas') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;padding-bottom:24px;">
      <a href="clientes_detalhe.php?cod=<?= $cliente_cod ?>&aba=financeiro" class="btn btn-outline">Cancelar</a>
      <button type="submit" class="btn btn-primario">
        <i class="material-icons-round"><?= $editar?'save':'add' ?></i>
        <?= $editar?'Guardar alterações':'Registar lançamento' ?>
      </button>
    </div>
  </form>
</main>

<script>
const radios = document.querySelectorAll('.radio-tipo');
const opts   = document.querySelectorAll('.tipo-opt');
function atualizar() {
  const val = document.querySelector('.radio-tipo:checked')?.value;
  opts.forEach(o => {
    const ativo = o.dataset.val === val;
    o.style.borderColor = ativo?(val==='receita'?'var(--verde)':'var(--vermelho)'):'var(--c200)';
    o.style.background  = ativo?(val==='receita'?'var(--verde-claro)':'var(--vermelho-claro)'):'';
  });
}
radios.forEach(r => r.addEventListener('change', atualizar));
opts.forEach((o,i)=>o.addEventListener('click',()=>{radios[i].checked=true;atualizar();}));
atualizar();
</script>

<?php require_once("footer.php"); ?>
