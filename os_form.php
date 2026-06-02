<?php
require_once("db/conexao.php");

$cod         = (int)($_GET['cod']         ?? 0);
$cliente_cod = (int)($_GET['cliente_cod'] ?? 0);
$editar      = $cod > 0;
$os          = [];
$erros       = [];

// Carrega OS para edição
if ($editar) {
    $r = mysqli_query($con, "SELECT * FROM ordens_servico WHERE cod=$cod");
    if (!$r || mysqli_num_rows($r) === 0) { header("Location: clientes.php"); exit; }
    $os = mysqli_fetch_assoc($r);
    $cliente_cod = $cliente_cod ?: (int)$os['cliente_cod'];
}

// Carrega cliente
if ($cliente_cod === 0) { header("Location: clientes.php"); exit; }
$rc = mysqli_query($con, "SELECT * FROM clientes WHERE cod=$cliente_cod");
if (!$rc || mysqli_num_rows($rc) === 0) { header("Location: clientes.php"); exit; }
$cliente = mysqli_fetch_assoc($rc);

// Gera número automático
function gerarNumeroOS($con) {
    $ano = date('Y');
    $t   = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT COUNT(*) AS t FROM ordens_servico WHERE YEAR(criado_em)=$ano"))['t'] ?? 0;
    return "OS-$ano-" . str_pad($t + 1, 4, '0', STR_PAD_LEFT);
}

// Processa POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = ['titulo','tipo_servico','prioridade','status','tecnico','equipamento',
               'problema_relatado','servico_realizado','pecas_utilizadas',
               'valor_servico','valor_pecas','data_prevista','observacoes','numero'];
    foreach ($campos as $c) $os[$c] = trim($_POST[$c] ?? '');

    if ($os['titulo'] === '') $erros[] = 'O título é obrigatório.';

    if (empty($erros)) {
        $val_srv  = (float)str_replace(',','.',$os['valor_servico']);
        $val_pec  = (float)str_replace(',','.',$os['valor_pecas']);
        $val_tot  = $val_srv + $val_pec;
        $uc       = (int)$_SESSION['cod'];
        $num      = $editar ? $os['numero'] : gerarNumeroOS($con);
        $dp       = $os['data_prevista'] ? "'".$os['data_prevista']."'" : "NULL";

        $v = [];
        foreach (['titulo','tipo_servico','prioridade','status','tecnico','equipamento',
                  'problema_relatado','servico_realizado','pecas_utilizadas','observacoes'] as $c) {
            $v[$c] = mysqli_real_escape_string($con, $os[$c]);
        }
        $num_esc = mysqli_real_escape_string($con, $num);

        if ($editar) {
            $conc = ($os['status']==='concluida' && ($os['status_ant']??'')!=='concluida') ? ",data_conclusao=NOW()" : "";
            mysqli_query($con, "UPDATE ordens_servico SET
                titulo='{$v['titulo']}', tipo_servico='{$v['tipo_servico']}',
                prioridade='{$v['prioridade']}', status='{$v['status']}',
                tecnico='{$v['tecnico']}', equipamento='{$v['equipamento']}',
                problema_relatado='{$v['problema_relatado']}',
                servico_realizado='{$v['servico_realizado']}',
                pecas_utilizadas='{$v['pecas_utilizadas']}',
                valor_servico=$val_srv, valor_pecas=$val_pec, valor_total=$val_tot,
                data_prevista=$dp, observacoes='{$v['observacoes']}' $conc
                WHERE cod=$cod");
            header("Location: clientes_detalhe.php?cod=$cliente_cod&aba=ordens&msg=os_edit"); exit;
        } else {
            mysqli_query($con, "INSERT INTO ordens_servico
                (numero,cliente_cod,titulo,tipo_servico,prioridade,status,tecnico,equipamento,
                 problema_relatado,servico_realizado,pecas_utilizadas,
                 valor_servico,valor_pecas,valor_total,data_prevista,observacoes,usuario_cod)
                VALUES ('$num_esc',$cliente_cod,'{$v['titulo']}','{$v['tipo_servico']}',
                '{$v['prioridade']}','{$v['status']}','{$v['tecnico']}','{$v['equipamento']}',
                '{$v['problema_relatado']}','{$v['servico_realizado']}','{$v['pecas_utilizadas']}',
                $val_srv,$val_pec,$val_tot,$dp,'{$v['observacoes']}',$uc)");
            header("Location: clientes_detalhe.php?cod=$cliente_cod&aba=ordens&msg=os_criada"); exit;
        }
    }
}

// Defaults
$os['prioridade'] = $os['prioridade'] ?? 'normal';
$os['status']     = $os['status']     ?? 'aberta';
$os['numero']     = $os['numero']     ?? gerarNumeroOS($con);

require_once("header.php");
function v($o,$k){ return htmlspecialchars($o[$k]??''); }
function s($o,$k,$v){ return ($o[$k]??'')===$v?'selected':''; }
?>

<main class="pagina" style="max-width:860px;">

  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="clientes_detalhe.php?cod=<?= $cliente_cod ?>&aba=ordens" class="btn-icone">
        <i class="material-icons-round">arrow_back</i>
      </a>
      <div class="page-header-txt">
        <h1 class="pagina-titulo"><?= $editar?'Editar ordem de serviço':'Nova ordem de serviço' ?></h1>
        <p class="pagina-subtitulo">
          Cliente: <strong><?= htmlspecialchars($cliente['nome_completo']) ?></strong>
        </p>
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

    <!-- Identificação -->
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Identificação</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo">
            <label class="form-label">Nº OS</label>
            <input type="text" name="numero" class="form-input"
                   value="<?= v($os,'numero') ?>" readonly
                   style="background:var(--c50);font-family:monospace;font-weight:600;color:var(--azul);">
          </div>
          <div class="form-grupo">
            <label class="form-label">Estado</label>
            <select name="status" class="form-select">
              <option value="aberta"       <?= s($os,'status','aberta')       ?>>🟠 Aberta</option>
              <option value="em_andamento" <?= s($os,'status','em_andamento') ?>>🟣 Em curso</option>
              <option value="aguardando"   <?= s($os,'status','aguardando')   ?>>🟡 Aguardando</option>
              <option value="concluida"    <?= s($os,'status','concluida')    ?>>🟢 Concluída</option>
              <option value="cancelada"    <?= s($os,'status','cancelada')    ?>>⚫ Cancelada</option>
            </select>
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Título / Assunto <span class="obg">*</span></label>
            <input type="text" name="titulo" class="form-input"
                   placeholder="Descreva brevemente o serviço"
                   value="<?= v($os,'titulo') ?>" required autofocus>
          </div>
          <div class="form-grupo">
            <label class="form-label">Tipo de serviço</label>
            <input type="text" name="tipo_servico" class="form-input"
                   placeholder="Ex: Suporte, Manutenção, Instalação, Formação"
                   value="<?= v($os,'tipo_servico') ?>" list="tipos-srv">
            <datalist id="tipos-srv">
              <option value="Suporte técnico"><option value="Manutenção preventiva">
              <option value="Manutenção correctiva"><option value="Instalação de software">
              <option value="Instalação de hardware"><option value="Configuração de rede">
              <option value="Formação"><option value="Consultoria">
            </datalist>
          </div>
          <div class="form-grupo">
            <label class="form-label">Prioridade</label>
            <select name="prioridade" class="form-select">
              <option value="baixa"   <?= s($os,'prioridade','baixa')   ?>>🔵 Baixa</option>
              <option value="normal"  <?= s($os,'prioridade','normal')  ?>>🟢 Normal</option>
              <option value="alta"    <?= s($os,'prioridade','alta')    ?>>🟠 Alta</option>
              <option value="urgente" <?= s($os,'prioridade','urgente') ?>>🔴 Urgente</option>
            </select>
          </div>
          <div class="form-grupo">
            <label class="form-label">Técnico responsável</label>
            <input type="text" name="tecnico" class="form-input"
                   placeholder="Nome do técnico"
                   value="<?= v($os,'tecnico') ?>"
                   list="tecnicos-lista">
            <datalist id="tecnicos-lista">
              <?php
              $tecs = mysqli_query($con,"SELECT DISTINCT tecnico FROM ordens_servico WHERE tecnico IS NOT NULL AND tecnico!='' ORDER BY tecnico");
              while ($t=mysqli_fetch_assoc($tecs)) echo "<option value='".htmlspecialchars($t['tecnico'])."'>";
              ?>
            </datalist>
          </div>
          <div class="form-grupo">
            <label class="form-label">Data prevista de conclusão</label>
            <input type="date" name="data_prevista" class="form-input"
                   value="<?= v($os,'data_prevista') ?>">
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Equipamento</label>
            <input type="text" name="equipamento" class="form-input"
                   placeholder="Ex: Portátil Dell Inspiron SN123456, Impressora HP"
                   value="<?= v($os,'equipamento') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Descrição técnica -->
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Descrição técnica</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo col-span-full">
            <label class="form-label">Problema relatado pelo cliente</label>
            <textarea name="problema_relatado" class="form-textarea" rows="3"
                      placeholder="Descreva o problema conforme relatado pelo cliente…"><?= v($os,'problema_relatado') ?></textarea>
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Serviço realizado / Diagnóstico</label>
            <textarea name="servico_realizado" class="form-textarea" rows="4"
                      placeholder="Descreva o que foi feito, diagnóstico técnico, solução aplicada…"><?= v($os,'servico_realizado') ?></textarea>
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Peças / Materiais utilizados</label>
            <textarea name="pecas_utilizadas" class="form-textarea" rows="2"
                      placeholder="Liste as peças ou materiais utilizados…"><?= v($os,'pecas_utilizadas') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Valores -->
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Valores</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-grupo">
            <label class="form-label">Valor do serviço (€)</label>
            <input type="number" name="valor_servico" id="v-srv" class="form-input calc-val"
                   step="0.01" min="0" placeholder="0,00"
                   value="<?= v($os,'valor_servico') ?>">
          </div>
          <div class="form-grupo">
            <label class="form-label">Valor das peças (€)</label>
            <input type="number" name="valor_pecas" id="v-pec" class="form-input calc-val"
                   step="0.01" min="0" placeholder="0,00"
                   value="<?= v($os,'valor_pecas') ?>">
          </div>
          <div class="form-grupo col-span-full">
            <label class="form-label">Total (€)</label>
            <input type="text" id="v-total" class="form-input"
                   readonly style="background:var(--c50);font-weight:700;font-size:16px;color:var(--azul);"
                   value="€ <?= number_format(($os['valor_total']??0),2,',','.') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Observações -->
    <div class="card mb-16">
      <div class="card-header"><span class="card-titulo">Observações internas</span></div>
      <div class="card-body">
        <div class="form-grupo" style="margin-bottom:0;">
          <textarea name="observacoes" class="form-textarea" rows="3"
                    placeholder="Notas internas, não visíveis no documento final…"><?= v($os,'observacoes') ?></textarea>
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;padding-bottom:24px;">
      <a href="clientes_detalhe.php?cod=<?= $cliente_cod ?>&aba=ordens" class="btn btn-outline">Cancelar</a>
      <button type="submit" class="btn btn-primario">
        <i class="material-icons-round"><?= $editar?'save':'build_circle' ?></i>
        <?= $editar?'Guardar alterações':'Criar ordem de serviço' ?>
      </button>
    </div>
  </form>
</main>

<script>
function calcTotal() {
  const srv = parseFloat(document.getElementById('v-srv').value)||0;
  const pec = parseFloat(document.getElementById('v-pec').value)||0;
  const tot = srv + pec;
  document.getElementById('v-total').value = '€ ' + tot.toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
}
document.querySelectorAll('.calc-val').forEach(el => el.addEventListener('input', calcTotal));
calcTotal();
</script>

<?php require_once("footer.php"); ?>
