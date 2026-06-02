<?php
require_once("db/conexao.php");

$cod = (int)($_GET['cod'] ?? 0);
if ($cod === 0) { header("Location: estoque.php"); exit; }

$r = mysqli_query($con,"SELECT e.*, c.nome AS cat_nome FROM estoque e JOIN estoque_categorias c ON e.categoria_cod=c.cod WHERE e.cod=$cod");
if (!$r || mysqli_num_rows($r)===0) { header("Location: estoque.php?msg=erro"); exit; }
$item = mysqli_fetch_assoc($r);

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_mov   = $_POST['tipo_mov']   ?? '';
    $quantidade = max(1,(int)($_POST['quantidade'] ?? 1));
    $motivo     = trim($_POST['motivo']    ?? '');
    $responsavel= trim($_POST['responsavel']?? '');
    $uc         = (int)$_SESSION['cod'];

    if (!in_array($tipo_mov,['entrada','saida','ajuste'])) $erros[] = 'Seleccione o tipo de movimentação.';
    if ($tipo_mov==='saida' && $quantidade > $item['quantidade'])
        $erros[] = "Quantidade insuficiente. Disponível: {$item['quantidade']} unidade(s).";

    if (empty($erros)) {
        $novaQtd = match($tipo_mov) {
            'entrada' => $item['quantidade'] + $quantidade,
            'saida'   => $item['quantidade'] - $quantidade,
            'ajuste'  => $quantidade,
        };
        $mot = mysqli_real_escape_string($con, $motivo);
        $res = mysqli_real_escape_string($con, $responsavel);
        mysqli_query($con,"UPDATE estoque SET quantidade=$novaQtd WHERE cod=$cod");
        mysqli_query($con,"INSERT INTO estoque_movimentacoes (estoque_cod,tipo_mov,quantidade,motivo,responsavel,usuario_cod) VALUES ($cod,'$tipo_mov',$quantidade,'$mot','$res',$uc)");
        header("Location: estoque.php?msg=movido"); exit;
    }
}

require_once("header.php");
?>

<main class="pagina" style="max-width:560px;">

  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="estoque.php" class="btn-icone"><i class="material-icons-round">arrow_back</i></a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo">Movimentar stock</h1>
        <p class="pagina-subtitulo">Registar entrada, saída ou ajuste</p>
      </div>
    </div>
  </div>

  <!-- Info do item -->
  <div class="card mb-16">
    <div class="card-body" style="display:flex;align-items:center;gap:14px;">
      <div style="width:46px;height:46px;border-radius:var(--r-md);flex-shrink:0;
                  background:<?= $item['tipo']==='material'?'var(--azul-claro)':'var(--roxo-claro)' ?>;
                  display:flex;align-items:center;justify-content:center;">
        <i class="material-icons-round" style="font-size:22px;color:<?= $item['tipo']==='material'?'var(--azul)':'var(--roxo)' ?>;">
          <?= $item['tipo']==='material'?'build':'computer' ?>
        </i>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-weight:600;font-size:15px;color:var(--c900);"><?= htmlspecialchars($item['nome']) ?></div>
        <div style="font-size:12px;color:var(--c500);margin-top:2px;">
          <?= htmlspecialchars($item['cat_nome']) ?>
          <?php if ($item['marca']||$item['modelo']): ?>
           · <?= htmlspecialchars(implode(' ',array_filter([$item['marca'],$item['modelo']]))) ?>
          <?php endif; ?>
        </div>
      </div>
      <div style="text-align:center;flex-shrink:0;">
        <div style="font-size:28px;font-weight:700;color:var(--c900);line-height:1;"><?= $item['quantidade'] ?></div>
        <div style="font-size:11px;color:var(--c500);">em stock</div>
      </div>
    </div>
  </div>

  <?php if (!empty($erros)): ?>
  <div class="alerta alerta-erro mb-16">
    <i class="material-icons-round">error_outline</i>
    <span><?= htmlspecialchars($erros[0]) ?></span>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header"><span class="card-titulo">Registar movimentação</span></div>
    <form method="POST">
      <div class="card-body" style="display:grid;gap:18px;">

        <!-- Tipo de movimentação -->
        <div>
          <label class="form-label">Tipo de movimentação</label>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
            <?php
            $tipos = [
              'entrada' => ['add_circle',    'Entrada',  'var(--verde)',    'var(--verde-claro)'],
              'saida'   => ['remove_circle', 'Saída',    'var(--vermelho)', 'var(--vermelho-claro)'],
              'ajuste'  => ['tune',          'Ajuste',   'var(--azul)',     'var(--azul-claro)'],
            ];
            foreach ($tipos as $val => [$ic,$lb,$cor,$bg]):
              $checked = ($_POST['tipo_mov']??'entrada')===$val?'checked':'';
            ?>
            <label style="cursor:pointer;">
              <input type="radio" name="tipo_mov" value="<?= $val ?>"
                     class="radio-mov" style="display:none;" <?= $checked ?>>
              <div class="mov-opt" data-cor="<?= $cor ?>" data-bg="<?= $bg ?>"
                   style="border:2px solid var(--c200);border-radius:var(--r-md);
                          padding:14px 10px;text-align:center;transition:all .15s;cursor:pointer;">
                <i class="material-icons-round" style="font-size:26px;color:<?= $cor ?>;display:block;margin-bottom:6px;"><?= $ic ?></i>
                <span style="font-size:13px;font-weight:500;"><?= $lb ?></span>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
          <div id="hint-tipo" style="font-size:12px;color:var(--c500);margin-top:6px;">
            Quantidade a adicionar ao stock actual
          </div>
        </div>

        <div class="form-grupo" style="margin-bottom:0;">
          <label class="form-label">Quantidade</label>
          <input type="number" name="quantidade" id="input-qtd" class="form-input" min="1"
                 value="<?= (int)($_POST['quantidade']??1) ?>">
        </div>
        <div class="form-grupo" style="margin-bottom:0;">
          <label class="form-label">Motivo / Descrição</label>
          <input type="text" name="motivo" class="form-input"
                 placeholder="Ex: Compra de material, Empréstimo ao sector X, Avaria…"
                 value="<?= htmlspecialchars($_POST['motivo']??'') ?>">
        </div>
        <div class="form-grupo" style="margin-bottom:0;">
          <label class="form-label">Responsável pela movimentação</label>
          <input type="text" name="responsavel" class="form-input"
                 placeholder="Nome do responsável"
                 value="<?= htmlspecialchars($_POST['responsavel']??($_SESSION['nome']??'')) ?>">
        </div>
      </div>
      <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px;">
        <a href="estoque.php" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primario">
          <i class="material-icons-round">swap_horiz</i> Confirmar movimentação
        </button>
      </div>
    </form>
  </div>
</main>

<script>
const radios = document.querySelectorAll('.radio-mov');
const opts   = document.querySelectorAll('.mov-opt');
const hint   = document.getElementById('hint-tipo');
const hints  = {
  entrada: 'Quantidade a adicionar ao stock actual',
  saida:   'Quantidade a retirar do stock (máx: <?= $item['quantidade'] ?>)',
  ajuste:  'Nova quantidade total em stock (substitui o valor actual)',
};
function atualizar() {
  const val = document.querySelector('.radio-mov:checked')?.value;
  opts.forEach(o => {
    const inp = o.closest('label').querySelector('input');
    const ativo = inp.value === val;
    o.style.borderColor = ativo ? o.dataset.cor : 'var(--c200)';
    o.style.background  = ativo ? o.dataset.bg  : '';
  });
  if (hint) hint.textContent = hints[val]||'';
  const qtd = document.getElementById('input-qtd');
  if (val==='saida') qtd.max = <?= $item['quantidade'] ?>;
  else qtd.removeAttribute('max');
}
radios.forEach(r => r.addEventListener('change', atualizar));
opts.forEach(o => o.addEventListener('click',()=>{o.closest('label').querySelector('input').checked=true;atualizar();}));
atualizar();
</script>
<?php require_once("footer.php"); ?>
