<?php
require_once("db/conexao.php");
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo    = trim($_POST['titulo']    ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if ($titulo === '') $erros[] = 'O título é obrigatório.';
    if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK)
        $erros[] = 'Seleccione um ficheiro para enviar.';
    if (!empty($_FILES['arquivo']['size']) && $_FILES['arquivo']['size'] > 10*1024*1024)
        $erros[] = 'O ficheiro não pode ser maior que 10MB.';

    if (empty($erros)) {
        $pasta = 'contrato/';
        if (!is_dir($pasta)) mkdir($pasta, 0755, true);
        $nome_unico = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['arquivo']['name']));
        $caminho = $pasta . $nome_unico;

        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho)) {
            $ti = mysqli_real_escape_string($con, $titulo);
            $de = mysqli_real_escape_string($con, $descricao);
            $ca = mysqli_real_escape_string($con, $caminho);
            mysqli_query($con, "INSERT INTO contratos (titulo,descricao,arquivo) VALUES ('$ti','$de','$ca')");
            header("Location: listar_contratos.php?msg=enviado"); exit;
        } else {
            $erros[] = 'Erro ao mover o ficheiro. Verifique as permissões da pasta.';
        }
    }
}

require_once("header.php");
?>

<main class="pagina" style="max-width:600px;">
  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="listar_contratos.php" class="btn-icone"><i class="material-icons-round">arrow_back</i></a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo">Enviar documento</h1>
        <p class="pagina-subtitulo">Faça upload de um contrato ou documento</p>
      </div>
    </div>
  </div>

  <?php if (!empty($erros)): ?>
  <div class="alerta alerta-erro mb-20">
    <i class="material-icons-round">error_outline</i>
    <div><?php foreach ($erros as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
  </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Dados do documento</span></div>
      <div class="card-body">
        <div class="form-grupo">
          <label class="form-label">Título <span class="obg">*</span></label>
          <input type="text" name="titulo" class="form-input"
                 placeholder="Nome ou identificação do documento"
                 value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>" required>
        </div>
        <div class="form-grupo">
          <label class="form-label">Descrição</label>
          <textarea name="descricao" class="form-textarea" rows="3"
                    placeholder="Descreva o conteúdo ou finalidade do documento…"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
        </div>
        <div class="form-grupo" style="margin-bottom:0;">
          <label class="form-label">Ficheiro <span class="obg">*</span></label>
          <div id="drop-zone"
               style="border:2px dashed var(--c300);border-radius:var(--r-md);padding:32px 20px;
                      text-align:center;cursor:pointer;transition:border-color .2s,background .2s;
                      background:var(--c50);position:relative;">
            <i class="material-icons-round" style="font-size:36px;color:var(--c400);display:block;margin-bottom:8px;">upload_file</i>
            <div style="font-size:14px;font-weight:500;color:var(--c700);" id="drop-texto">
              Arraste o ficheiro aqui ou clique para seleccionar
            </div>
            <div style="font-size:12px;color:var(--c500);margin-top:4px;">
              PDF, Word, Excel, imagens — máx. 10MB
            </div>
            <input type="file" name="arquivo" id="arq-input" required
                   style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;"
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.zip,.rar,.txt">
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;padding-bottom:24px;">
      <a href="listar_contratos.php" class="btn btn-outline">Cancelar</a>
      <button type="submit" class="btn btn-primario">
        <i class="material-icons-round">upload</i> Enviar documento
      </button>
    </div>
  </form>
</main>

<script>
const dz    = document.getElementById('drop-zone');
const input = document.getElementById('arq-input');
const texto = document.getElementById('drop-texto');
input.addEventListener('change', () => {
  if (input.files.length > 0) {
    texto.textContent = '✓ ' + input.files[0].name;
    dz.style.borderColor = 'var(--verde)';
    dz.style.background  = 'var(--verde-claro)';
  }
});
dz.addEventListener('dragover',  e => { e.preventDefault(); dz.style.borderColor='var(--azul)'; dz.style.background='var(--azul-claro)'; });
dz.addEventListener('dragleave', ()=> { dz.style.borderColor='var(--c300)'; dz.style.background='var(--c50)'; });
dz.addEventListener('drop', e => {
  e.preventDefault();
  if (e.dataTransfer.files.length>0) {
    const dt = new DataTransfer();
    dt.items.add(e.dataTransfer.files[0]);
    input.files = dt.files;
    texto.textContent = '✓ ' + e.dataTransfer.files[0].name;
    dz.style.borderColor='var(--verde)'; dz.style.background='var(--verde-claro)';
  }
});
</script>
<?php require_once("footer.php"); ?>
