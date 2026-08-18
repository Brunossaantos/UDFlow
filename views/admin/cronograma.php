<?php

/**
 * views/admin/cronograma.php
 * Renderizado por AdminCronogramaController::tela()
 * Espera $cronograma, $automacoes, $unidades, $clientes, $paginaAtual, $totalPaginas, $totalRegistros
 */

use Udflow\util\Saida;

$paginaAtiva = 'admin-cronograma';
$tituloPagina = 'Administração · Cronograma';
require __DIR__ . '/../partials/cabecalho.php';
?>

<div class="flex items-center justify-between mb-1">
  <h1 class="font-display font-semibold text-xl">Cronograma automático</h1>
  <button onclick="document.getElementById('modal-novo-agendamento').classList.remove('hidden'); document.getElementById('modal-novo-agendamento').classList.add('flex')"
    class="grad-flow text-[#04342C] font-semibold text-xs rounded-lg px-4 py-2 flex items-center gap-1.5">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
      <path d="M12 5v14M5 12h14" />
    </svg>Novo agendamento
  </button>
</div>
<p class="text-tsecondary text-sm mb-2">Dias e horários em que cada cliente dispara sozinho.</p>
<p class="text-tmuted text-xs mb-6">Importante: ativar/pausar aqui atualiza o registro no UDFlow, mas ainda não desliga o cron dentro do n8n sozinho - isso depende da integração descrita no README.</p>

<form method="GET" action="index.php" class="flex flex-wrap gap-3 mb-5">
  <input type="hidden" name="pagina" value="admin-cronograma">
  <select name="automacao_id" onchange="this.form.submit()" class="bg-surface border border-bord rounded-lg px-3 py-2 text-xs glow-focus">
    <option value="">Todas as automações</option>
    <?php foreach ($automacoes as $automacao): ?>
      <option value="<?= (int) $automacao['id'] ?>" <?= (int) ($_GET['automacao_id'] ?? 0) === (int) $automacao['id'] ? 'selected' : '' ?>><?= Saida::e($automacao['nome']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="unidade_id" onchange="this.form.submit()" class="bg-surface border border-bord rounded-lg px-3 py-2 text-xs glow-focus">
    <option value="">Todas as unidades</option>
    <?php foreach ($unidades as $u): ?>
      <option value="<?= (int) $u['id'] ?>" <?= (int) ($_GET['unidade_id'] ?? 0) === (int) $u['id'] ? 'selected' : '' ?>><?= Saida::e($u['nome']) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<div class="bg-surface border border-bord rounded-2xl overflow-hidden">
  <table class="w-full text-sm">
    <thead>
      <tr class="border-b border-bord text-left text-tmuted text-xs">
        <th class="font-medium px-5 py-3">Automação</th>
        <th class="font-medium px-5 py-3">Cliente</th>
        <th class="font-medium px-5 py-3">Unidade</th>
        <th class="font-medium px-5 py-3">Dia</th>
        <th class="font-medium px-5 py-3">Horário</th>
        <th class="font-medium px-5 py-3">Status</th>
        <th class="font-medium px-5 py-3 text-right">Ação</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($cronograma)): ?>
        <tr>
          <td colspan="7" class="px-5 py-10 text-center text-tmuted text-sm">Nenhum item de cronograma cadastrado ainda.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($cronograma as $item): ?>
          <tr class="border-b border-bordsoft last:border-0 hover:bg-elevated/40 transition">
            <td class="px-5 py-3.5 text-xs"><?= Saida::e($item['automacao_nome']) ?></td>
            <td class="px-5 py-3.5 font-medium"><?= Saida::e($item['cliente_nome']) ?></td>
            <td class="px-5 py-3.5 text-tsecondary text-xs"><?= Saida::e($item['unidade_nome']) ?></td>
            <td class="px-5 py-3.5 font-mono text-xs">
              <?= $item['frequencia'] === 'diaria' ? 'Todo dia' : 'Dia ' . (int) $item['dia_mes'] ?>
              <?php if (!empty($item['dias_semana'])): ?>
                <?php
                $rotulos = ['1' => 'Seg', '2' => 'Ter', '3' => 'Qua', '4' => 'Qui', '5' => 'Sex', '6' => 'Sáb', '7' => 'Dom'];
                $diasFormatados = array_map(fn($d) => $rotulos[$d] ?? $d, explode(',', $item['dias_semana']));
                ?>
                <br><span class="text-tmuted">(<?= Saida::e(implode(', ', $diasFormatados)) ?>)</span>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3.5 font-mono text-xs"><?= Saida::e(substr($item['horario'], 0, 5)) ?></td>
            <td class="px-5 py-3.5">
              <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" onchange="alternarAtivoCronograma(<?= (int) $item['id'] ?>, this.checked)" <?= $item['ativo'] ? 'checked' : '' ?> class="rounded border-bord">
                <span class="text-xs <?= $item['ativo'] ? 'text-success' : 'text-tmuted' ?>"><?= $item['ativo'] ? 'Ativo' : 'Pausado' ?></span>
              </label>
            </td>
            <td class="px-5 py-3.5 text-right flex gap-2 justify-end">
              <button onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>)" class="text-xs text-flow hover:underline">Editar</button>
              <button onclick="executarAgoraCronograma(<?= (int) $item['id'] ?>)" class="text-xs text-flow hover:underline">Executar agora</button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Paginação -->
<div class="flex items-center justify-between mt-5">
  <p class="text-xs text-tmuted">
    Mostrando <?= count($cronograma) ?> de <?= $totalRegistros ?? 0 ?> registros
  </p>
  <div class="flex gap-1">
    <?php if ($paginaAtual > 1): ?>
      <a href="index.php?pagina=admin-cronograma&p=1<?= isset($_GET['automacao_id']) ? '&automacao_id=' . (int) $_GET['automacao_id'] : '' ?><?= isset($_GET['unidade_id']) ? '&unidade_id=' . (int) $_GET['unidade_id'] : '' ?>"
        class="px-3 py-1.5 text-xs border border-bord rounded-lg hover:bg-surface transition">&laquo;</a>
      <a href="index.php?pagina=admin-cronograma&p=<?= $paginaAtual - 1 ?><?= isset($_GET['automacao_id']) ? '&automacao_id=' . (int) $_GET['automacao_id'] : '' ?><?= isset($_GET['unidade_id']) ? '&unidade_id=' . (int) $_GET['unidade_id'] : '' ?>"
        class="px-3 py-1.5 text-xs border border-bord rounded-lg hover:bg-surface transition">&lsaquo;</a>
    <?php endif; ?>

    <span class="px-3 py-1.5 text-xs"><?= $paginaAtual ?? 1 ?> / <?= $totalPaginas ?? 1 ?></span>

    <?php if (($paginaAtual ?? 1) < ($totalPaginas ?? 1)): ?>
      <a href="index.php?pagina=admin-cronograma&p=<?= ($paginaAtual ?? 1) + 1 ?><?= isset($_GET['automacao_id']) ? '&automacao_id=' . (int) $_GET['automacao_id'] : '' ?><?= isset($_GET['unidade_id']) ? '&unidade_id=' . (int) $_GET['unidade_id'] : '' ?>"
        class="px-3 py-1.5 text-xs border border-bord rounded-lg hover:bg-surface transition">&rsaquo;</a>
      <a href="index.php?pagina=admin-cronograma&p=<?= $totalPaginas ?? 1 ?><?= isset($_GET['automacao_id']) ? '&automacao_id=' . (int) $_GET['automacao_id'] : '' ?><?= isset($_GET['unidade_id']) ? '&unidade_id=' . (int) $_GET['unidade_id'] : '' ?>"
        class="px-3 py-1.5 text-xs border border-bord rounded-lg hover:bg-surface transition">&raquo;</a>
    <?php endif; ?>
  </div>
</div>

<!-- MODAL NOVO AGENDAMENTO -->
<div id="modal-novo-agendamento" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
  <div class="w-full max-w-md bg-elevated border border-bord rounded-2xl p-6">
    <div class="flex items-center justify-between mb-5">
      <h3 class="font-display font-semibold text-base">Novo agendamento</h3>
      <button onclick="document.getElementById('modal-novo-agendamento').classList.add('hidden'); document.getElementById('modal-novo-agendamento').classList.remove('flex')" class="text-tmuted hover:text-tprimary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M6 6l12 12M18 6L6 18" />
        </svg></button>
    </div>

    <form method="POST" action="index.php?pagina=admin-cronograma-criar" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= Saida::e($_SESSION['csrf_token'] ?? '') ?>">

      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Automação</label>
        <select name="automacao_id" required class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
          <option value="">Selecione</option>
          <?php foreach ($automacoes as $automacao): ?>
            <option value="<?= (int) $automacao['id'] ?>"><?= Saida::e($automacao['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Cliente</label>
        <select name="cliente_id" required class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
          <option value="">Selecione</option>
          <?php foreach ($clientes as $cliente): ?>
            <option value="<?= (int) $cliente->id ?>"><?= Saida::e($cliente->nomeExibicao) ?> · <?= Saida::e($cliente->unidadeNome) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Frequência</label>
        <select id="campo-frequencia" name="frequencia" required onchange="alternarCampoDiaMes()" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
          <option value="diaria">Todo dia</option>
          <option value="mensal">Uma vez por mês</option>
        </select>
      </div>

      <div id="bloco-dia-mes" class="hidden">
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Dia do mês</label>
        <input type="number" name="dia_mes" min="1" max="31" placeholder="Ex: 10" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
      </div>

      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Dias da semana <span class="text-tmuted font-normal">· nenhum marcado = todo dia</span></label>
        <div class="grid grid-cols-7 gap-1.5">
          <?php $diasLabel = ['1' => 'Seg', '2' => 'Ter', '3' => 'Qua', '4' => 'Qui', '5' => 'Sex', '6' => 'Sáb', '7' => 'Dom']; ?>
          <?php foreach ($diasLabel as $valor => $rotulo): ?>
            <label class="flex flex-col items-center gap-1 bg-surface border border-bord rounded-lg py-2 text-[10px] text-tsecondary cursor-pointer has-[:checked]:border-flow has-[:checked]:text-flow">
              <input type="checkbox" name="dias_semana[]" value="<?= $valor ?>" class="rounded border-bord">
              <?= $rotulo ?>
            </label>
          <?php endforeach; ?>
        </div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5 mt-3">Horário</label>
        <input type="time" name="horario" required class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
      </div>

      <div class="flex gap-3 pt-2">
        <button type="button" onclick="document.getElementById('modal-novo-agendamento').classList.add('hidden'); document.getElementById('modal-novo-agendamento').classList.remove('flex')" class="flex-1 border border-bord rounded-lg py-2.5 text-sm font-medium hover:bg-surface transition">Cancelar</button>
        <button type="submit" class="flex-1 grad-flow text-[#04342C] font-semibold rounded-lg py-2.5 text-sm hover:opacity-90 transition">Criar agendamento</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDITAR AGENDAMENTO -->
<div id="modal-editar-agendamento" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
  <div class="w-full max-w-md bg-elevated border border-bord rounded-2xl p-6">
    <div class="flex items-center justify-between mb-5">
      <h3 class="font-display font-semibold text-base">Editar agendamento</h3>
      <button onclick="fecharModalEditar()" class="text-tmuted hover:text-tprimary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M6 6l12 12M18 6L6 18" />
        </svg></button>
    </div>

    <form method="POST" action="index.php?pagina=admin-cronograma-editar" class="space-y-4" id="form-editar">
      <input type="hidden" name="csrf_token" value="<?= Saida::e($_SESSION['csrf_token'] ?? '') ?>">
      <input type="hidden" name="cronograma_id" id="edit-id">

      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Automação</label>
        <select id="edit-automacao" disabled class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus opacity-60">
          <?php foreach ($automacoes as $automacao): ?>
            <option value="<?= (int) $automacao['id'] ?>"><?= Saida::e($automacao['nome']) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="text-[10px] text-tmuted mt-1">Não pode ser alterada</p>
      </div>

      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Cliente</label>
        <select id="edit-cliente" disabled class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus opacity-60">
          <?php foreach ($clientes as $cliente): ?>
            <option value="<?= (int) $cliente->id ?>"><?= Saida::e($cliente->nomeExibicao) ?> · <?= Saida::e($cliente->unidadeNome) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="text-[10px] text-tmuted mt-1">Não pode ser alterada</p>
      </div>

      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Frequência</label>
        <select id="edit-frequencia" name="frequencia" required onchange="alternarCampoDiaMesEditar()" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
          <option value="diaria">Todo dia</option>
          <option value="mensal">Uma vez por mês</option>
        </select>
      </div>

      <div id="bloco-dia-mes-edit" class="hidden">
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Dia do mês</label>
        <input type="number" name="dia_mes" id="edit-dia-mes" min="1" max="31" placeholder="Ex: 10" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
      </div>

      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Dias da semana <span class="text-tmuted font-normal">· nenhum marcado = todo dia</span></label>
        <div class="grid grid-cols-7 gap-1.5">
          <?php $diasLabel = ['1' => 'Seg', '2' => 'Ter', '3' => 'Qua', '4' => 'Qui', '5' => 'Sex', '6' => 'Sáb', '7' => 'Dom']; ?>
          <?php foreach ($diasLabel as $valor => $rotulo): ?>
            <label class="flex flex-col items-center gap-1 bg-surface border border-bord rounded-lg py-2 text-[10px] text-tsecondary cursor-pointer has-[:checked]:border-flow has-[:checked]:text-flow">
              <input type="checkbox" name="dias_semana[]" value="<?= $valor ?>" class="rounded border-bord">
              <?= $rotulo ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Horário</label>
        <input type="time" name="horario" id="edit-horario" required class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
      </div>

      <div class="flex gap-3 pt-2">
        <button type="button" onclick="fecharModalEditar()" class="flex-1 border border-bord rounded-lg py-2.5 text-sm font-medium hover:bg-surface transition">Cancelar</button>
        <button type="submit" class="flex-1 grad-flow text-[#04342C] font-semibold rounded-lg py-2.5 text-sm hover:opacity-90 transition">Salvar alterações</button>
      </div>
    </form>
  </div>
</div>

<script>
  async function alternarAtivoCronograma(id, ativo) {
    const resultado = await enviarComCsrf('index.php?pagina=admin-cronograma-ativo', {
      cronograma_id: id,
      ativo: ativo ? '1' : '0'
    });
    if (resultado.sucesso) {
      toast(ativo ? 'Agendamento ativado' : 'Agendamento pausado');
    } else {
      toast(resultado.mensagem || 'Não foi possível atualizar.', 'erro');
    }
  }

  async function executarAgoraCronograma(id) {
    const resultado = await enviarComCsrf('index.php?pagina=admin-cronograma-executar', {
      cronograma_id: id
    });
    if (resultado.sucesso) {
      toast('Execução manual disparada');
    } else {
      toast(resultado.mensagem || 'Não foi possível executar.', 'erro');
    }
  }

  function alternarCampoDiaMes() {
    const ehMensal = document.getElementById('campo-frequencia').value === 'mensal';
    document.getElementById('bloco-dia-mes').classList.toggle('hidden', !ehMensal);
  }

  function abrirModalEditar(item) {
    document.getElementById('edit-id').value = item.id;
    document.getElementById('edit-automacao').value = item.automacao_id;
    document.getElementById('edit-cliente').value = item.cliente_id;
    document.getElementById('edit-frequencia').value = item.frequencia;
    document.getElementById('edit-dia-mes').value = item.dia_mes || '';
    document.getElementById('edit-horario').value = (item.horario || '').substring(0, 5);

    document.querySelectorAll('#modal-editar-agendamento input[name="dias_semana[]"]').forEach(checkbox => {
      checkbox.checked = false;
    });

    if (item.dias_semana) {
      const dias = item.dias_semana.split(',');
      dias.forEach(dia => {
        const checkbox = document.querySelector(`#modal-editar-agendamento input[name="dias_semana[]"][value="${dia}"]`);
        if (checkbox) checkbox.checked = true;
      });
    }

    alternarCampoDiaMesEditar();
    document.getElementById('modal-editar-agendamento').classList.remove('hidden');
    document.getElementById('modal-editar-agendamento').classList.add('flex');
  }

  function fecharModalEditar() {
    document.getElementById('modal-editar-agendamento').classList.add('hidden');
    document.getElementById('modal-editar-agendamento').classList.remove('flex');
  }

  function alternarCampoDiaMesEditar() {
    const ehMensal = document.getElementById('edit-frequencia').value === 'mensal';
    document.getElementById('bloco-dia-mes-edit').classList.toggle('hidden', !ehMensal);
  }
</script>

<?php require __DIR__ . '/../partials/rodape.php'; ?>