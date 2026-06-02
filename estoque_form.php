<?php
require_once("db/conexao.php");

$cod    = (int)($_GET['cod'] ?? 0);
$editar = $cod > 0;
$item   = [];
$erros  = [];

if ($editar) {
    $r = mysqli_query($con, "SELECT * FROM estoque WHERE cod=$cod");
    if (!$r || mysqli_num_rows($r) === 0) { header("Location: estoque.php?msg=erro"); exit; }
    $item = mysqli_fetch_assoc($r);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = ['tipo','categoria_cod','nome','marca','modelo','numero_serie',
               'patrimonio','quantidade','qtd_minima','localizacao','responsavel','status','observacoes'];
    foreach ($campos as $c) $item[$c] = trim($_POST[$c] ?? '');
    $item['quantidade'] = max(0,(int)$item['quantidade']);
    $item['qtd_minima'] = max(0,(int)$item['qtd_minima']);

    if ($item['nome']==='')                                 $erros[] = 'O nome do item é obrigatório.';
    if (!in_array($item['tipo'],['material','equipamento'])) $erros[] = 'Seleccione um tipo válido.';
    if ((int)$item['categoria_cod']===0)                    $erros[] = 'Seleccione uma categoria.';

    if (empty($erros)) {
        $v   = [];
        foreach (['tipo','nome','marca','modelo','numero_serie','patrimonio',
                  'localizacao','responsavel','status','observacoes'] as $c) {
            $v[$c] = mysqli_real_escape_string($con, $item[$c]);
        }
        $cat = (int)$item['categoria_cod'];
        $qty = (int)$item['quantidade'];
        $min = (int)$item['qtd_minima'];
        $uc  = (int)$_SESSION['cod'];

        if ($editar) {
            mysqli_query($con, "UPDATE estoque SET
                tipo='{$v['tipo']}', categoria_cod=$cat, nome='{$v['nome']}',
                marca='{$v['marca']}', modelo='{$v['modelo']}',
                numero_serie='{$v['numero_serie']}', patrimonio='{$v['patrimonio']}',
                quantidade=$qty, qtd_minima=$min,
                localizacao='{$v['localizacao']}', responsavel='{$v['responsavel']}',
                status='{$v['status']}', observacoes='{$v['observacoes']}'
                WHERE cod=$cod");
            header("Location: estoque.php?msg=editado"); exit;
        } else {
            mysqli_query($con, "INSERT INTO estoque
                (tipo,categoria_cod,nome,marca,modelo,numero_serie,patrimonio,
                 quantidade,qtd_minima,localizacao,responsavel,status,observacoes)
                VALUES ('{$v['tipo']}',$cat,'{$v['nome']}','{$v['marca']}','{$v['modelo']}',
                '{$v['numero_serie']}','{$v['patrimonio']}',$qty,$min,
                '{$v['localizacao']}','{$v['responsavel']}','{$v['status']}','{$v['observacoes']}')");
            $novo = mysqli_insert_id($con);
            if ($qty > 0)
                mysqli_query($con,"INSERT INTO estoque_movimentacoes (estoque_cod,tipo_mov,quantidade,motivo,usuario_cod) VALUES ($novo,'entrada',$qty,'Entrada inicial',$uc)");
            header("Location: estoque.php?msg=criado"); exit;
        }
    }
}

$item['status']    = $item['status']    ?? 'disponivel';
$item['tipo']      = $item['tipo']      ?? 'material';
$item['quantidade']= $item['quantidade']?? 0;
$item['qtd_minima']= $item['qtd_minima']?? 1;

$categorias = mysqli_query($con, "SELECT * FROM estoque_categorias ORDER BY tipo, nome");

require_once("header.php");
function v($i,$k){ return htmlspecialchars($i[$k] ?? ''); }
function s($i,$k,$v){ return ($i[$k]??'')===$v?'selected':''; }
?>

<main class="pagina" style="max-width:820px;">

  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="estoque.php" class="btn-icone"><i class="material-icons-round">arrow_back</i></a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo"><?= $editar?'Editar item':'Novo item de stock' ?></h1>
        <p class="pagina-subtitulo"><?= $editar?'Actualizar informações do item':'Preencha os dados do novo item' ?></p>
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

    <!-- Tipo -->
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Tipo de item</span></div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <label style="cursor:pointer;">
            <input type="radio" name="tipo" value="material" class="radio-tipo" style="display:none;"
                   <?= ($item['tipo']??'material')==='material'?'checked':'' ?>>
            <div class="tipo-opt" data-val="material"
                 style="border:2px solid var(--c200);border-radius:var(--r-md);padding:14px 18px;
                        display:flex;align-items:center;gap:12px;transition:all .15s;">
              <div style="width:38px;height:38px;border-radius:50%;background:var(--azul-claro);
                          display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="material-icons-round" style="color:var(--azul);font-size:20px;">build</i>
              </div>
              <div>
                <div style="font-weight:600;color:var(--c800);">Material / Peça</div>
                <div style="font-size:12px;color:var(--c500);">Cabos, memórias, discos, acessórios…</div>
              </div>
            </div>
          </label>
          <label style="cursor:pointer;">
            <input type="radio" name="tipo" value="equipamento" class="radio-tipo" style="display:none;"
                   <?= ($item['tipo']??'')==='equipamento'?'checked':'' ?>>
            <div class="tipo-opt" data-val="equipamento"
                 style="border:2px solid var(--c200);border-radius:var(--r-md);padding:14px 18px;
                        display:flex;align-items:center;gap:12px;transition:all .15s;">
              <div style="width:38px;height:38px;border-radius:50%;background:var(--roxo-claro);
                          display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="material-icons-round" style="color:var(--roxo);font-size:20px;">computer</i>
              </div>
              <div>
                <div style="font-weight:600;color:var(--c800);">Equipamento completo</div>
                <div style="font-size:12px;color:var(--c500);">Portáteis, routers, monitores…</div>
              </div>
            </div>
          </label>
        </div>
      </div>
    </div>

    <!-- Identificação -->
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Identificação</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo">
            <label class="form-label">Categoria <span class="obg">*</span></label>
            <select name="categoria_cod" class="form-select" required>
              <option value="">Seleccione…</option>
              <?php
              $tipo_ant = '';
              while ($cat = mysqli_fetch_assoc($categorias)):
                if ($cat['tipo'] !== $tipo_ant) {
                    if ($tipo_ant !== '') echo '</optgroup>';
                    $lbl = $cat['tipo']==='material'?'Materiais':($cat['tipo']==='equipamento'?'Equipamentos':'Ambos');
                    echo "<optgroup label='$lbl'>";
                    $tipo_ant = $cat['tipo'];
                }
              ?>
              <option value="<?= $cat['cod'] ?>" <?= (int)($item['categoria_cod']??0)===(int)$cat['cod']?'selected':'' ?>>
                <?= htmlspecialchars($cat['nome']) ?>
              </option>
              <?php endwhile; if ($tipo_ant) echo '</optgroup>'; ?>
            </select>
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Nome do item <span class="obg">*</span></label>
            <input type="text" name="nome" class="form-input"
                   placeholder="Ex: Memória RAM DDR4 8GB, Portátil Dell Inspiron…"
                   value="<?= v($item,'nome') ?>" required>
          </div>
          <div class="form-grupo">
            <label class="form-label">Marca</label>
            <input type="text" name="marca" class="form-input" placeholder="Ex: Kingston, Dell, TP-Link"
                   value="<?= v($item,'marca') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Modelo</label>
            <input type="text" name="modelo" class="form-input" placeholder="Ex: KVR3200-8G"
                   value="<?= v($item,'modelo') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Número de série (S/N)</label>
            <input type="text" name="numero_serie" class="form-input" placeholder="Serial number"
                   value="<?= v($item,'numero_serie') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Nº Patrimonial</label>
            <input type="text" name="patrimonio" class="form-input" placeholder="Código patrimonial"
                   value="<?= v($item,'patrimonio') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Controlo -->
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Controlo de quantidade e localização</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-4">
          <div class="form-grupo">
            <label class="form-label">Quantidade</label>
            <input type="number" name="quantidade" class="form-input" min="0"
                   value="<?= (int)($item['quantidade']??0) ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Qtd. mínima</label>
            <input type="number" name="qtd_minima" class="form-input" min="0"
                   value="<?= (int)($item['qtd_minima']??1) ?>">
            <div class="form-hint">Alerta de stock mínimo</div>
          </div>
          <div class="form-grupo">
            <label class="form-label">Estado</label>
            <select name="status" class="form-select">
              <option value="disponivel" <?= s($item,'status','disponivel') ?>>Disponível</option>
              <option value="em_uso"     <?= s($item,'status','em_uso')     ?>>Em uso</option>
              <option value="manutencao" <?= s($item,'status','manutencao') ?>>Manutenção</option>
              <option value="baixado"    <?= s($item,'status','baixado')    ?>>Abatido</option>
            </select>
          </div>
          <div class="form-grupo">
            <label class="form-label">Responsável</label>
            <input type="text" name="responsavel" class="form-input" placeholder="Nome"
                   value="<?= v($item,'responsavel') ?>">
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Localização</label>
            <input type="text" name="localizacao" class="form-input"
                   placeholder="Ex: Armazém, Sala Técnica, Prateleira A3…"
                   value="<?= v($item,'localizacao') ?>">
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Observações</label>
            <textarea name="observacoes" class="form-textarea" rows="3"
                      placeholder="Estado do item, condições de uso, notas…"><?= v($item,'observacoes') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;padding-bottom:24px;">
      <a href="estoque.php" class="btn btn-outline">Cancelar</a>
      <button type="submit" class="btn btn-primario">
        <i class="material-icons-round"><?= $editar?'save':'add' ?></i>
        <?= $editar?'Guardar alterações':'Adicionar ao stock' ?>
      </button>
    </div>
  </form>
</main>

<script>
const radios = document.querySelectorAll('.radio-tipo');
const opts   = document.querySelectorAll('.tipo-opt');
function atualizarTipo() {
  const val = document.querySelector('.radio-tipo:checked')?.value;
  opts.forEach(o => {
    const ativo = o.dataset.val === val;
    o.style.borderColor = ativo?(val==='material'?'var(--azul)':'var(--roxo)'):'var(--c200)';
    o.style.background  = ativo?(val==='material'?'var(--azul-claro)':'var(--roxo-claro)'):'';
  });
}
radios.forEach(r => r.addEventListener('change', atualizarTipo));
opts.forEach((o,i) => o.addEventListener('click',()=>{radios[i].checked=true;atualizarTipo();}));
atualizarTipo();
</script>
<?php require_once("footer.php"); ?>
