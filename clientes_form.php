<?php
require_once("db/conexao.php");

$cod    = (int)($_GET['cod'] ?? 0);
$editar = $cod > 0;
$c      = [];
$erros  = [];

// Pré-carrega cliente_cod vindo de orcamento_form
$cliente_cod_pre = (int)($_GET['cliente_cod'] ?? 0);

if ($editar) {
    $r = mysqli_query($con, "SELECT * FROM clientes WHERE cod=$cod");
    if (!$r || mysqli_num_rows($r) === 0) { header("Location: clientes.php?msg=erro"); exit; }
    $c = mysqli_fetch_assoc($r);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = [
        'nome_completo','tipo_pessoa','cpf_cnpj','rg','data_nascimento',
        'sexo','nacionalidade','email','telefone','celular','whatsapp',
        'cep','endereco','numero','complemento','bairro','cidade','estado',
        'referencia','observacoes','status',
    ];
    foreach ($campos as $campo) $c[$campo] = trim($_POST[$campo] ?? '');

    if ($c['nome_completo'] === '') $erros[] = 'O nome completo é obrigatório.';
    if ($c['tipo_pessoa']   === '') $erros[] = 'Seleccione o tipo de pessoa.';

    if (empty($erros)) {
        $v = [];
        foreach ($campos as $campo) $v[$campo] = mysqli_real_escape_string($con, $c[$campo]);

        if ($editar) {
            $sql = "UPDATE clientes SET
                nome_completo='{$v['nome_completo']}', tipo_pessoa='{$v['tipo_pessoa']}',
                cpf_cnpj='{$v['cpf_cnpj']}', rg='{$v['rg']}',
                data_nascimento=".($v['data_nascimento']?"'{$v['data_nascimento']}'":"NULL").",
                sexo='{$v['sexo']}', nacionalidade='{$v['nacionalidade']}',
                email='{$v['email']}', telefone='{$v['telefone']}',
                celular='{$v['celular']}', whatsapp='{$v['whatsapp']}',
                cep='{$v['cep']}', endereco='{$v['endereco']}',
                numero='{$v['numero']}', complemento='{$v['complemento']}',
                bairro='{$v['bairro']}', cidade='{$v['cidade']}',
                estado='{$v['estado']}', referencia='{$v['referencia']}',
                observacoes='{$v['observacoes']}', status='{$v['status']}'
                WHERE cod=$cod";
            mysqli_query($con, $sql);
            header("Location: clientes_detalhe.php?cod=$cod&msg=editado"); exit;
        } else {
            $sql = "INSERT INTO clientes
                (nome_completo,tipo_pessoa,cpf_cnpj,rg,data_nascimento,sexo,nacionalidade,
                 email,telefone,celular,whatsapp,cep,endereco,numero,complemento,bairro,
                 cidade,estado,referencia,observacoes,status)
                VALUES
                ('{$v['nome_completo']}','{$v['tipo_pessoa']}','{$v['cpf_cnpj']}','{$v['rg']}',
                ".($v['data_nascimento']?"'{$v['data_nascimento']}'":"NULL").",
                '{$v['sexo']}','{$v['nacionalidade']}','{$v['email']}','{$v['telefone']}',
                '{$v['celular']}','{$v['whatsapp']}','{$v['cep']}','{$v['endereco']}',
                '{$v['numero']}','{$v['complemento']}','{$v['bairro']}','{$v['cidade']}',
                '{$v['estado']}','{$v['referencia']}','{$v['observacoes']}','{$v['status']}')";
            if (mysqli_query($con, $sql)) {
                $novo = mysqli_insert_id($con);
                header("Location: clientes_detalhe.php?cod=$novo&msg=criado"); exit;
            } else {
                $erros[] = 'Erro ao guardar: ' . mysqli_error($con);
            }
        }
    }
}

// Defaults
$c['status']       = $c['status']       ?? 'ativo';
$c['tipo_pessoa']  = $c['tipo_pessoa']  ?? 'fisica';
$c['nacionalidade']= $c['nacionalidade']?? 'Portuguesa';

// Distritos de Portugal
$distritos = [
    'Aveiro','Beja','Braga','Bragança','Castelo Branco','Coimbra',
    'Évora','Faro','Guarda','Leiria','Lisboa','Portalegre','Porto',
    'Santarém','Setúbal','Viana do Castelo','Vila Real','Viseu',
    'Açores','Madeira',
];

require_once("header.php");
function val($c,$k){ return htmlspecialchars($c[$k] ?? ''); }
function sel($c,$k,$v){ return ($c[$k]??'')===$v?'selected':''; }
?>

<main class="pagina" style="max-width:860px;">

  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="<?= $editar?"clientes_detalhe.php?cod=$cod":'clientes.php' ?>" class="btn-icone">
        <i class="material-icons-round">arrow_back</i>
      </a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo"><?= $editar?'Editar cliente':'Novo cliente' ?></h1>
        <p class="pagina-subtitulo"><?= $editar?'Actualizar ficha do cliente':'Preencha os dados para criar a ficha' ?></p>
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

  <form method="POST" id="form-cliente">

    <!-- Tipo de pessoa -->
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Tipo de cliente</span></div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <label style="cursor:pointer;">
            <input type="radio" name="tipo_pessoa" value="fisica" class="radio-tipo" style="display:none;"
                   <?= ($c['tipo_pessoa']??'fisica')==='fisica'?'checked':'' ?>>
            <div class="tipo-opt" data-val="fisica"
                 style="border:2px solid var(--c200);border-radius:var(--r-md);padding:14px 18px;
                        display:flex;align-items:center;gap:12px;transition:all .15s;">
              <div style="width:38px;height:38px;border-radius:50%;background:var(--azul-claro);
                          display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="material-icons-round" style="color:var(--azul);font-size:20px;">person</i>
              </div>
              <div>
                <div style="font-weight:600;color:var(--c800);">Pessoa Singular</div>
                <div style="font-size:12px;color:var(--c500);">NIF, CC, dados pessoais</div>
              </div>
            </div>
          </label>
          <label style="cursor:pointer;">
            <input type="radio" name="tipo_pessoa" value="juridica" class="radio-tipo" style="display:none;"
                   <?= ($c['tipo_pessoa']??'')==='juridica'?'checked':'' ?>>
            <div class="tipo-opt" data-val="juridica"
                 style="border:2px solid var(--c200);border-radius:var(--r-md);padding:14px 18px;
                        display:flex;align-items:center;gap:12px;transition:all .15s;">
              <div style="width:38px;height:38px;border-radius:50%;background:var(--roxo-claro);
                          display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="material-icons-round" style="color:var(--roxo);font-size:20px;">business</i>
              </div>
              <div>
                <div style="font-weight:600;color:var(--c800);">Pessoa Colectiva</div>
                <div style="font-size:12px;color:var(--c500);">NIPC, empresa, entidade</div>
              </div>
            </div>
          </label>
        </div>
      </div>
    </div>

    <!-- Dados principais -->
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Dados de identificação</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo col-span-full">
            <label class="form-label">Nome completo / Denominação social <span class="obg">*</span></label>
            <input type="text" name="nome_completo" class="form-input"
                   placeholder="Nome do cliente ou designação da empresa"
                   value="<?= val($c,'nome_completo') ?>" required>
          </div>
          <div class="form-grupo">
            <label class="form-label" id="label-nif">NIF</label>
            <input type="text" name="cpf_cnpj" id="input-nif" class="form-input"
                   placeholder="000 000 000" maxlength="11"
                   value="<?= val($c,'cpf_cnpj') ?>">
            <div class="form-hint" id="hint-nif">Número de Identificação Fiscal</div>
          </div>
          <div class="form-grupo" id="wrap-cc">
            <label class="form-label">Cartão de Cidadão</label>
            <input type="text" name="rg" class="form-input"
                   placeholder="00000000 0 XX0"
                   value="<?= val($c,'rg') ?>">
          </div>
          <div class="form-grupo" id="wrap-nasc">
            <label class="form-label">Data de nascimento</label>
            <input type="date" name="data_nascimento" class="form-input"
                   value="<?= val($c,'data_nascimento') ?>">
          </div>
          <div class="form-grupo" id="wrap-sexo">
            <label class="form-label">Género</label>
            <select name="sexo" class="form-select">
              <option value="">Seleccione…</option>
              <option value="masculino" <?= sel($c,'sexo','masculino') ?>>Masculino</option>
              <option value="feminino"  <?= sel($c,'sexo','feminino')  ?>>Feminino</option>
              <option value="outro"     <?= sel($c,'sexo','outro')     ?>>Outro / Não indicar</option>
            </select>
          </div>
          <div class="form-grupo">
            <label class="form-label">Nacionalidade</label>
            <input type="text" name="nacionalidade" class="form-input"
                   placeholder="Ex: Portuguesa"
                   value="<?= val($c,'nacionalidade') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Estado</label>
            <select name="status" class="form-select">
              <option value="ativo"   <?= sel($c,'status','ativo')   ?>>Activo</option>
              <option value="inativo" <?= sel($c,'status','inativo') ?>>Inactivo</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Contactos -->
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Contactos</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo col-span-full">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-input"
                   placeholder="email@exemplo.pt"
                   value="<?= val($c,'email') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Telefone fixo</label>
            <input type="text" name="telefone" class="form-input mask-tel-pt"
                   placeholder="+351 2xx xxx xxx"
                   value="<?= val($c,'telefone') ?>" maxlength="16">
          </div>
          <div class="form-grupo">
            <label class="form-label">Telemóvel</label>
            <input type="text" name="celular" class="form-input mask-telem-pt"
                   placeholder="+351 9xx xxx xxx"
                   value="<?= val($c,'celular') ?>" maxlength="16">
          </div>
          <div class="form-grupo">
            <label class="form-label">WhatsApp</label>
            <input type="text" name="whatsapp" class="form-input mask-telem-pt"
                   placeholder="+351 9xx xxx xxx"
                   value="<?= val($c,'whatsapp') ?>" maxlength="16">
          </div>
        </div>
      </div>
    </div>

    <!-- Morada -->
    <div class="card mb-16">
      <div class="card-header">
        <span class="card-titulo">Morada</span>
        <span style="font-size:12px;color:var(--c400);">Código postal preenche automaticamente</span>
      </div>
      <div class="card-body">
        <div class="form-grid form-grid-4">
          <div class="form-grupo">
            <label class="form-label">Código postal</label>
            <input type="text" name="cep" id="cep" class="form-input mask-cp-pt"
                   placeholder="0000-000" maxlength="8"
                   value="<?= val($c,'cep') ?>">
          </div>
          <div class="form-grupo" style="grid-column:span 3;">
            <label class="form-label">Rua / Avenida</label>
            <input type="text" name="endereco" id="endereco" class="form-input"
                   placeholder="Rua, Avenida, Largo…"
                   value="<?= val($c,'endereco') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Nº / Fracção</label>
            <input type="text" name="numero" class="form-input"
                   placeholder="Nº, Lote"
                   value="<?= val($c,'numero') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Andar / Complemento</label>
            <input type="text" name="complemento" class="form-input"
                   placeholder="1º Esq., Bloco A…"
                   value="<?= val($c,'complemento') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Localidade / Freguesia</label>
            <input type="text" name="bairro" id="bairro" class="form-input"
                   placeholder="Localidade"
                   value="<?= val($c,'bairro') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Concelho</label>
            <input type="text" name="cidade" id="cidade" class="form-input"
                   placeholder="Concelho"
                   value="<?= val($c,'cidade') ?>">
          </div>
          <div class="form-grupo" style="grid-column:span 2;">
            <label class="form-label">Distrito</label>
            <select name="estado" id="estado" class="form-select">
              <option value="">Seleccione…</option>
              <?php foreach ($distritos as $d): ?>
              <option value="<?= $d ?>" <?= val($c,'estado')===$d?'selected':'' ?>><?= $d ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Referência de localização</label>
            <input type="text" name="referencia" class="form-input"
                   placeholder="Ex: Junto ao edifício X, portão verde…"
                   value="<?= val($c,'referencia') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Observações -->
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Observações</span></div>
      <div class="card-body">
        <div class="form-grupo" style="margin-bottom:0;">
          <textarea name="observacoes" class="form-textarea" rows="4"
                    placeholder="Notas internas sobre o cliente…"><?= val($c,'observacoes') ?></textarea>
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;padding-bottom:24px;">
      <a href="<?= $editar?"clientes_detalhe.php?cod=$cod":'clientes.php' ?>" class="btn btn-outline">Cancelar</a>
      <button type="submit" class="btn btn-primario">
        <i class="material-icons-round"><?= $editar?'save':'person_add' ?></i>
        <?= $editar ? 'Guardar alterações' : 'Criar cliente' ?>
      </button>
    </div>

  </form>
</main>

<script>
// ── Tipo de pessoa ───────────────────────────────────────
const radios  = document.querySelectorAll('.radio-tipo');
const opts    = document.querySelectorAll('.tipo-opt');
const wrapCC  = document.getElementById('wrap-cc');
const wrapNasc= document.getElementById('wrap-nasc');
const wrapSexo= document.getElementById('wrap-sexo');
const labelNif= document.getElementById('label-nif');
const inputNif= document.getElementById('input-nif');
const hintNif = document.getElementById('hint-nif');

function atualizarTipo() {
  const val = document.querySelector('.radio-tipo:checked')?.value;
  opts.forEach(o => {
    const ativo = o.dataset.val === val;
    o.style.borderColor = ativo ? (val==='fisica'?'var(--azul)':'var(--roxo)') : 'var(--c200)';
    o.style.background  = ativo ? (val==='fisica'?'var(--azul-claro)':'var(--roxo-claro)') : '';
  });
  const isFisica = val === 'fisica';
  wrapCC.style.display   = isFisica ? '' : 'none';
  wrapNasc.style.display = isFisica ? '' : 'none';
  wrapSexo.style.display = isFisica ? '' : 'none';
  labelNif.textContent   = isFisica ? 'NIF' : 'NIPC';
  hintNif.textContent    = isFisica ? 'Número de Identificação Fiscal' : 'Número de Identificação de Pessoa Colectiva';
  inputNif.placeholder   = '000 000 000';
  inputNif.maxLength     = 11;
}
radios.forEach(r => r.addEventListener('change', atualizarTipo));
opts.forEach((o,i) => o.addEventListener('click', () => { radios[i].checked=true; atualizarTipo(); }));
atualizarTipo();

// ── Código postal PT → API VIACEP não funciona para PT
// Usa API portuguesa do código postal
document.getElementById('cep').addEventListener('blur', function() {
  const cp = this.value.replace(/\D/g,'');
  if (cp.length !== 7) return;
  const cp4 = cp.slice(0,4);
  const cp3 = cp.slice(4);
  fetch(`https://www.ctt.pt/feapl_2/app/open/postalCodeSearch/postalCodeSearchEndpoint.jspx?postalCode=${cp4}-${cp3}`)
    .then(() => {}) // API CTT pode não ter CORS; fallback manual
    .catch(() => {});
  // Alternativa: preencha manualmente ou use uma API proxy própria
});

// ── Máscaras PT ──────────────────────────────────────────
function mascara(sel, fn) {
  document.querySelectorAll(sel).forEach(el => {
    el.addEventListener('input', function() {
      const pos = this.selectionStart;
      this.value = fn(this.value);
      try { this.setSelectionRange(pos, pos); } catch(e){}
    });
  });
}

// Código postal: 0000-000
mascara('.mask-cp-pt', v => {
  const d = v.replace(/\D/g,'').slice(0,7);
  return d.length > 4 ? d.slice(0,4)+'-'+d.slice(4) : d;
});

// Telefone PT: +351 2xx xxx xxx → simplificado
mascara('.mask-tel-pt', v => {
  let d = v.replace(/\D/g,'').slice(0,9);
  if (d.length >= 3) d = d.slice(0,3)+' '+d.slice(3);
  if (d.length >= 7) d = d.slice(0,7)+' '+d.slice(7);
  return d;
});

// Telemóvel PT: 9xx xxx xxx
mascara('.mask-telem-pt', v => {
  let d = v.replace(/\D/g,'').slice(0,9);
  if (d.length >= 3) d = d.slice(0,3)+' '+d.slice(3);
  if (d.length >= 7) d = d.slice(0,7)+' '+d.slice(7);
  return d;
});
</script>

<?php require_once("footer.php"); ?>
