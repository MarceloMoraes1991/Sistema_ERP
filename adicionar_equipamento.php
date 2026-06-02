<?php
require_once("db/conexao.php");

$cod    = (int)($_GET['cod'] ?? 0);
$editar = $cod > 0;
$e      = [];
$erros  = [];

if ($editar) {
    $r = mysqli_query($con, "SELECT * FROM controle_equipamentos WHERE cod=$cod");
    if (!$r || mysqli_num_rows($r) === 0) { header("Location: listar_equipamento.php?msg=erro"); exit; }
    $e = mysqli_fetch_assoc($r);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = ['nome_funcionario','cpf','equipamento','modelo','sn','fabricante','mtm','mo','descricao'];
    foreach ($campos as $c) $e[$c] = trim($_POST[$c] ?? '');

    if ($e['equipamento'] === '') $erros[] = 'O nome do equipamento é obrigatório.';

    if (empty($erros)) {
        $v = [];
        foreach ($campos as $c) $v[$c] = mysqli_real_escape_string($con, $e[$c]);

        if ($editar) {
            mysqli_query($con, "UPDATE controle_equipamentos SET
                nome_funcionario='{$v['nome_funcionario']}', cpf='{$v['cpf']}',
                equipamento='{$v['equipamento']}', modelo='{$v['modelo']}',
                sn='{$v['sn']}', fabricante='{$v['fabricante']}',
                mtm='{$v['mtm']}', mo='{$v['mo']}', descricao='{$v['descricao']}'
                WHERE cod=$cod");
            header("Location: listar_equipamento.php?msg=editado"); exit;
        } else {
            mysqli_query($con, "INSERT INTO controle_equipamentos
                (nome_funcionario,cpf,equipamento,modelo,sn,fabricante,mtm,mo,descricao)
                VALUES ('{$v['nome_funcionario']}','{$v['cpf']}','{$v['equipamento']}',
                '{$v['modelo']}','{$v['sn']}','{$v['fabricante']}',
                '{$v['mtm']}','{$v['mo']}','{$v['descricao']}')");
            header("Location: listar_equipamento.php?msg=criado"); exit;
        }
    }
}

require_once("header.php");
function v($e,$k){ return htmlspecialchars($e[$k] ?? ''); }
?>

<main class="pagina" style="max-width:780px;">
  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="listar_equipamento.php" class="btn-icone"><i class="material-icons-round">arrow_back</i></a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo"><?= $editar?'Editar equipamento':'Novo equipamento' ?></h1>
        <p class="pagina-subtitulo"><?= $editar?'Actualizar dados do equipamento':'Registar equipamento para um colaborador' ?></p>
      </div>
    </div>
  </div>

  <?php if (!empty($erros)): ?>
  <div class="alerta alerta-erro mb-20">
    <i class="material-icons-round">error_outline</i>
    <span><?= htmlspecialchars($erros[0]) ?></span>
  </div>
  <?php endif; ?>

  <form method="POST">
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Dados do colaborador</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo col-span-full">
            <label class="form-label">Nome do colaborador</label>
            <input type="text" name="nome_funcionario" class="form-input"
                   placeholder="Nome completo do responsável pelo equipamento"
                   value="<?= v($e,'nome_funcionario') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">NIF</label>
            <input type="text" name="cpf" class="form-input"
                   placeholder="000 000 000" maxlength="11"
                   value="<?= v($e,'cpf') ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Dados do equipamento</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo col-span-full">
            <label class="form-label">Tipo / Nome do equipamento <span class="obg">*</span></label>
            <input type="text" name="equipamento" class="form-input"
                   placeholder="Ex: Portátil, Computador fixo, Monitor, Teclado…"
                   value="<?= v($e,'equipamento') ?>" required>
          </div>
          <div class="form-grupo">
            <label class="form-label">Fabricante / Marca</label>
            <input type="text" name="fabricante" class="form-input"
                   placeholder="Ex: Dell, Lenovo, HP, Asus"
                   value="<?= v($e,'fabricante') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Modelo</label>
            <input type="text" name="modelo" class="form-input"
                   placeholder="Ex: Inspiron 15, ThinkPad E14"
                   value="<?= v($e,'modelo') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Número de série (S/N)</label>
            <input type="text" name="sn" class="form-input"
                   placeholder="Serial number do equipamento"
                   value="<?= v($e,'sn') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">MTM</label>
            <input type="text" name="mtm" class="form-input"
                   placeholder="Machine Type Model"
                   value="<?= v($e,'mtm') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">MO / Opção</label>
            <input type="text" name="mo" class="form-input"
                   placeholder="Modelo/opção"
                   value="<?= v($e,'mo') ?>">
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Descrição / Observações</label>
            <textarea name="descricao" class="form-textarea" rows="4"
                      placeholder="Estado do equipamento, acessórios incluídos, notas gerais…"><?= v($e,'descricao') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;padding-bottom:24px;">
      <a href="listar_equipamento.php" class="btn btn-outline">Cancelar</a>
      <button type="submit" class="btn btn-primario">
        <i class="material-icons-round"><?= $editar?'save':'add' ?></i>
        <?= $editar?'Guardar alterações':'Registar equipamento' ?>
      </button>
    </div>
  </form>
</main>
<?php require_once("footer.php"); ?>
