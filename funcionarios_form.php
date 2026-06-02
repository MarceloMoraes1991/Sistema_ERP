<?php
require_once("db/conexao.php");

$cod    = (int)($_GET['cod'] ?? 0);
$editar = $cod > 0;
$f      = [];
$erros  = [];

if ($editar) {
    $r = mysqli_query($con, "SELECT * FROM funcionarios WHERE cod=$cod");
    if (!$r || mysqli_num_rows($r) === 0) { header("Location: funcionarios.php?msg=erro"); exit; }
    $f = mysqli_fetch_assoc($r);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = [
        'nome','cargo','departamento','email','telefone','telemovel',
        'nif','cc','data_nascimento','data_admissao','data_saida',
        'tipo_contrato','salario','iban',
        'cep','endereco','numero','complemento','bairro','cidade','estado',
        'observacoes','status',
    ];
    foreach ($campos as $c) $f[$c] = trim($_POST[$c] ?? '');

    if ($f['nome'] === '') $erros[] = 'O nome é obrigatório.';

    if (empty($erros)) {
        $v = [];
        foreach ($campos as $c) $v[$c] = mysqli_real_escape_string($con, $f[$c]);

        $dn  = $v['data_nascimento']  ? "'{$v['data_nascimento']}'"  : "NULL";
        $da  = $v['data_admissao']    ? "'{$v['data_admissao']}'"    : "NULL";
        $ds  = $v['data_saida']       ? "'{$v['data_saida']}'"       : "NULL";
        $sal = is_numeric($f['salario']) ? (float)$f['salario']       : "NULL";

        if ($editar) {
            mysqli_query($con, "UPDATE funcionarios SET
                nome='{$v['nome']}', cargo='{$v['cargo']}', departamento='{$v['departamento']}',
                email='{$v['email']}', telefone='{$v['telefone']}', telemovel='{$v['telemovel']}',
                nif='{$v['nif']}', cc='{$v['cc']}',
                data_nascimento=$dn, data_admissao=$da, data_saida=$ds,
                tipo_contrato='{$v['tipo_contrato']}', salario=$sal, iban='{$v['iban']}',
                cep='{$v['cep']}', endereco='{$v['endereco']}', numero='{$v['numero']}',
                complemento='{$v['complemento']}', bairro='{$v['bairro']}',
                cidade='{$v['cidade']}', estado='{$v['estado']}',
                observacoes='{$v['observacoes']}', status='{$v['status']}'
                WHERE cod=$cod");
            header("Location: funcionarios_detalhe.php?cod=$cod&msg=editado"); exit;
        } else {
            mysqli_query($con, "INSERT INTO funcionarios
                (nome,cargo,departamento,email,telefone,telemovel,nif,cc,
                 data_nascimento,data_admissao,data_saida,tipo_contrato,salario,iban,
                 cep,endereco,numero,complemento,bairro,cidade,estado,observacoes,status)
                VALUES
                ('{$v['nome']}','{$v['cargo']}','{$v['departamento']}',
                 '{$v['email']}','{$v['telefone']}','{$v['telemovel']}',
                 '{$v['nif']}','{$v['cc']}',
                 $dn,$da,$ds,'{$v['tipo_contrato']}',$sal,'{$v['iban']}',
                 '{$v['cep']}','{$v['endereco']}','{$v['numero']}',
                 '{$v['complemento']}','{$v['bairro']}','{$v['cidade']}',
                 '{$v['estado']}','{$v['observacoes']}','{$v['status']}')");
            $novo = mysqli_insert_id($con);
            header("Location: funcionarios_detalhe.php?cod=$novo&msg=criado"); exit;
        }
    }
}

// Defaults
$f['status']        = $f['status']       ?? 'ativo';
$f['tipo_contrato'] = $f['tipo_contrato']?? 'efetivo';

$distritos = [
    'Aveiro','Beja','Braga','Bragança','Castelo Branco','Coimbra',
    'Évora','Faro','Guarda','Leiria','Lisboa','Portalegre','Porto',
    'Santarém','Setúbal','Viana do Castelo','Vila Real','Viseu',
    'Açores','Madeira',
];

require_once("header.php");
function v($f,$k){ return htmlspecialchars($f[$k] ?? ''); }
function s($f,$k,$v){ return ($f[$k]??'')===$v?'selected':''; }
?>

<main class="pagina" style="max-width:860px;">

  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="<?= $editar?"funcionarios_detalhe.php?cod=$cod":'funcionarios.php' ?>" class="btn-icone">
        <i class="material-icons-round">arrow_back</i>
      </a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo"><?= $editar?'Editar funcionário':'Novo funcionário' ?></h1>
        <p class="pagina-subtitulo"><?= $editar?'Actualizar ficha do colaborador':'Registar novo colaborador' ?></p>
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

  <form method="POST">

    <!-- Dados pessoais -->
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Dados pessoais</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo col-span-full">
            <label class="form-label">Nome completo <span class="obg">*</span></label>
            <input type="text" name="nome" class="form-input"
                   placeholder="Nome completo do colaborador"
                   value="<?= v($f,'nome') ?>" required autofocus>
          </div>
          <div class="form-grupo">
            <label class="form-label">NIF</label>
            <input type="text" name="nif" class="form-input"
                   placeholder="000 000 000" maxlength="11"
                   value="<?= v($f,'nif') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Cartão de Cidadão</label>
            <input type="text" name="cc" class="form-input"
                   placeholder="00000000 0 XX0"
                   value="<?= v($f,'cc') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Data de nascimento</label>
            <input type="date" name="data_nascimento" class="form-input"
                   value="<?= v($f,'data_nascimento') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Estado</label>
            <select name="status" class="form-select">
              <option value="ativo"   <?= s($f,'status','ativo')   ?>>Activo</option>
              <option value="ferias"  <?= s($f,'status','ferias')  ?>>Férias</option>
              <option value="baixa"   <?= s($f,'status','baixa')   ?>>Baixa médica</option>
              <option value="inativo" <?= s($f,'status','inativo') ?>>Inactivo</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Dados profissionais -->
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Dados profissionais</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo">
            <label class="form-label">Cargo / Função</label>
            <input type="text" name="cargo" class="form-input"
                   placeholder="Ex: Técnico de TI, Gestor de Projecto"
                   value="<?= v($f,'cargo') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Departamento</label>
            <input type="text" name="departamento" class="form-input"
                   placeholder="Ex: Suporte, Desenvolvimento, Comercial"
                   value="<?= v($f,'departamento') ?>" list="deptos-lista">
            <datalist id="deptos-lista">
              <option value="Suporte Técnico">
              <option value="Desenvolvimento">
              <option value="Comercial">
              <option value="Administração">
              <option value="Recursos Humanos">
              <option value="Financeiro">
            </datalist>
          </div>
          <div class="form-grupo">
            <label class="form-label">Tipo de contrato</label>
            <select name="tipo_contrato" class="form-select">
              <option value="efetivo"            <?= s($f,'tipo_contrato','efetivo')            ?>>Efectivo</option>
              <option value="termo_certo"        <?= s($f,'tipo_contrato','termo_certo')        ?>>Termo certo</option>
              <option value="termo_incerto"      <?= s($f,'tipo_contrato','termo_incerto')      ?>>Termo incerto</option>
              <option value="prestacao_servicos" <?= s($f,'tipo_contrato','prestacao_servicos') ?>>Prestação de serviços</option>
              <option value="estagio"            <?= s($f,'tipo_contrato','estagio')            ?>>Estágio</option>
              <option value="outro"              <?= s($f,'tipo_contrato','outro')              ?>>Outro</option>
            </select>
          </div>
          <div class="form-grupo">
            <label class="form-label">Salário bruto (€)</label>
            <input type="number" name="salario" class="form-input"
                   placeholder="0,00" step="0.01" min="0"
                   value="<?= v($f,'salario') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Data de admissão</label>
            <input type="date" name="data_admissao" class="form-input"
                   value="<?= v($f,'data_admissao') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Data de saída</label>
            <input type="date" name="data_saida" class="form-input"
                   value="<?= v($f,'data_saida') ?>">
            <div class="form-hint">Preencha apenas se o colaborador já saiu</div>
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">IBAN</label>
            <input type="text" name="iban" class="form-input"
                   placeholder="PT50 0000 0000 0000 0000 0000 0"
                   value="<?= v($f,'iban') ?>" maxlength="30">
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
            <label class="form-label">E-mail profissional</label>
            <input type="email" name="email" class="form-input"
                   placeholder="colaborador@itm.pt"
                   value="<?= v($f,'email') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Telefone fixo</label>
            <input type="text" name="telefone" class="form-input"
                   placeholder="2xx xxx xxx"
                   value="<?= v($f,'telefone') ?>" maxlength="15">
          </div>
          <div class="form-grupo">
            <label class="form-label">Telemóvel</label>
            <input type="text" name="telemovel" class="form-input"
                   placeholder="9xx xxx xxx"
                   value="<?= v($f,'telemovel') ?>" maxlength="15">
          </div>
        </div>
      </div>
    </div>

    <!-- Morada -->
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Morada</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-4">
          <div class="form-grupo">
            <label class="form-label">Código postal</label>
            <input type="text" name="cep" class="form-input"
                   placeholder="0000-000" maxlength="8"
                   value="<?= v($f,'cep') ?>">
          </div>
          <div class="form-grupo" style="grid-column:span 3;">
            <label class="form-label">Rua / Avenida</label>
            <input type="text" name="endereco" class="form-input"
                   placeholder="Rua, Avenida, Largo…"
                   value="<?= v($f,'endereco') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Nº / Fracção</label>
            <input type="text" name="numero" class="form-input"
                   placeholder="Nº"
                   value="<?= v($f,'numero') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Andar / Complemento</label>
            <input type="text" name="complemento" class="form-input"
                   placeholder="1º Esq., Bloco A…"
                   value="<?= v($f,'complemento') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Localidade</label>
            <input type="text" name="bairro" class="form-input"
                   placeholder="Localidade / Freguesia"
                   value="<?= v($f,'bairro') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Concelho</label>
            <input type="text" name="cidade" class="form-input"
                   placeholder="Concelho"
                   value="<?= v($f,'cidade') ?>">
          </div>
          <div class="form-grupo" style="grid-column:span 2;">
            <label class="form-label">Distrito</label>
            <select name="estado" class="form-select">
              <option value="">Seleccione…</option>
              <?php foreach ($distritos as $d): ?>
              <option value="<?= $d ?>" <?= v($f,'estado')===$d?'selected':'' ?>><?= $d ?></option>
              <?php endforeach; ?>
            </select>
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
                    placeholder="Notas internas sobre o colaborador…"><?= v($f,'observacoes') ?></textarea>
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;padding-bottom:24px;">
      <a href="<?= $editar?"funcionarios_detalhe.php?cod=$cod":'funcionarios.php' ?>" class="btn btn-outline">Cancelar</a>
      <button type="submit" class="btn btn-primario">
        <i class="material-icons-round"><?= $editar?'save':'person_add' ?></i>
        <?= $editar?'Guardar alterações':'Registar funcionário' ?>
      </button>
    </div>

  </form>
</main>

<?php require_once("footer.php"); ?>
