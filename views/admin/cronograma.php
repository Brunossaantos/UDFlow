<?php

/**
 * views/admin/cronograma.php
 * Renderizado por AdminCronogramaController::tela()
 * Espera $cronograma (agrupado), $automacoes, $unidades, $clientes, $paginaAtual, $totalPaginas, $totalRegistros
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
        <th class="font-medium px-5 py-3">Frequência</th>
        <th class="font-medium px-5 py-3">Horários</th>
        <th class="font-medium px-5 py-3 text-right">Ação</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($cronograma)): ?>
        <tr>
          <td colspan="6" class="px-5 py-10 text-center text-tmuted text-sm">Nenhum item de cronograma cadastrado ainda.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($cronograma as $grupo): ?>
          <tr class="border-b border-bordsoft last:border-0 hover:bg-elevated/40 transition">
            <td class="px-5 py-3.5 text-xs"><?= Saida::e($grupo['automacao_nome']) ?></td>
            <td class="px-5 py-3.5 font-medium"><?= Saida::e($grupo['cliente_nome']) ?></td>
            <td class="px-5 py-3.5 text-tsecondary text-xs"><?= Saida::e($grupo['unidade_nome']) ?></td>
            
            <!-- Frequência do primeiro horário -->
            <td class="px-5 py-3.5 text-xs">
              <?php $primeiro = $grupo['horarios'][0]; ?>
              <?= $primeiro['frequencia'] === 'diaria' ? 'Todo dia' : 'Dia ' . (int) $primeiro['dia_mes'] ?>
              <?php if (!empty($primeiro['dias_semana'])): ?>
                <?php
                $rotulos = ['1' => 'Seg', '2' => 'Ter', '3' => 'Qua', '4' => 'Qui', '5' => 'Sex', '6' => 'Sáb', '7' => 'Dom'];
                $diasFormatados = array_map(fn($d) => $rotulos[$d] ?? $d, explode(',', $primeiro['dias_semana']));
                ?>
                <br><span class="text-tmuted text-[10px]">(<?= Saida::e(implode(', ', $diasFormatados)) ?>)</span>
              <?php endif; ?>
            </td>

            <!-- Lista de horários -->
            <td class="px-5 py-3.5">
              <div class="flex flex-wrap gap-1.5">
                <?php foreach ($grupo['horarios'] as $horario): ?>
                  <span class="inline-flex items-center gap-1 px-2 py-1 bg-flow/10 text-flow text-xs rounded font-mono">
                    <?= Saida::e(substr($horario['horario'], 0, 5)) ?>
                    <?php if (!$horario['ativo']): ?>
                      <span class="text-[8px] opacity-60">(pausado)</span>
                    <?php endif; ?>
                  </span>
                <?php endforeach; ?>
              </div>
              <p class="text-[10px] text-tmuted mt-1"><?= count($grupo['horarios']) ?> horário(s)</p>
            </td>

            <!-- Botão Gerenciar -->
            <td class="px-5 py-3.5 text-right">
              <button onclick="abrirModalGerenciar(<?= htmlspecialchars(json_encode($grupo), ENT_QUOTES, 'UTF-8') ?>)" 
                class="text-xs text-flow hover:underline">
                Gerenciar
              </button>
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
    Mostrando <?= count($cronograma) ?> de <?= $totalRegistros ?? 0 ?> agendamento(s)
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

<!-- MODAL GERENCIAR MÚLTIPLOS HORÁRIOS -->
<div id="modal-gerenciar-grupo" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
  <div class="w-full max-w-lg bg-elevated border border-bord rounded-2xl p-6">
    <div class="flex items-center justify-between mb-5">
      <div>
        <h3 class="font-display font-semibold text-base" id="modal-titulo"></h3>
        <p class="text-xs text-tmuted mt-1" id="modal-subtitulo"></p>
      </div>
      <button onclick="fecharModalGerenciar()" class="text-tmuted hover:text-tprimary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M6 6l12 12M18 6L6 18" />
        </svg>
      </button>
    </div>

    <div id="lista-horarios" class="space-y-2 mb-5 max-h-96 overflow-y-auto">
      <!-- Preenchido via JavaScript -->
    </div>

    <div class="flex gap-2">
      <button onclick="abrirModalNovoHorario()" class="flex-1 border border-flow text-flow text-xs font-medium rounded-lg py-2.5 hover:bg-flow/10 transition">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="inline mr-1">
          <path d="M12 5v14M5 12h14" />
        </svg>Adicionar horário
      </button>
      <button onclick="fecharModalGerenciar()" class="flex-1 border border-bord rounded-lg py-2.5 text-sm font-medium hover:bg-surface transition">
        Fechar
      </button>
    </div>
  </div>
</div>

<script>
  function alternarCampoDiaMes() {
    const ehMensal = document.getElementById('campo-frequencia').value === 'mensal';
    document.getElementById('bloco-dia-mes').classList.toggle('hidden', !ehMensal);
  }

  function abrirModalGerenciar(grupo) {
    // DEBUG
    console.log('Grupo recebido:', grupo);
    console.log('Horários:', grupo.horarios);
    
    document.getElementById('modal-titulo').textContent = grupo.automacao_nome + ' · ' + grupo.cliente_nome;
    document.getElementById('modal-subtitulo').textContent = grupo.unidade_nome;
    
    const lista = document.getElementById('lista-horarios');
    lista.innerHTML = '';
    
    if (!grupo.horarios || grupo.horarios.length === 0) {
      lista.innerHTML = '<p class="text-tmuted text-xs">Nenhum horário</p>';
      document.getElementById('modal-gerenciar-grupo').classList.remove('hidden');
      document.getElementById('modal-gerenciar-grupo').classList.add('flex');
      return;
    }
    
    grupo.horarios.forEach(h => {
      const div = document.createElement('div');
      div.className = 'flex items-center justify-between p-3 bg-surface border border-bord rounded-lg';
      div.innerHTML = `
        <div>
          <span class="text-sm font-mono">${h.horario.substring(0, 5)}</span>
          <span class="text-xs text-tmuted ml-3">${h.ativo ? '✓ Ativo' : '✗ Pausado'}</span>
        </div>
        <div class="flex gap-2">
          <button onclick="editarHorario(${h.id})" class="text-xs text-blue-500 hover:underline">
            Editar
          </button>
          <button onclick="alternarHorario(${h.id}, ${!h.ativo ? 1 : 0})" class="text-xs text-flow hover:underline">
            ${h.ativo ? 'Pausar' : 'Ativar'}
          </button>
          <button onclick="deletarHorario(${h.id})" class="text-xs text-red-500 hover:underline">
            Deletar
          </button>
        </div>
      `;
      lista.appendChild(div);
    });
    
    document.getElementById('modal-gerenciar-grupo').classList.remove('hidden');
    document.getElementById('modal-gerenciar-grupo').classList.add('flex');
  }

  function fecharModalGerenciar() {
    document.getElementById('modal-gerenciar-grupo').classList.add('hidden');
    document.getElementById('modal-gerenciar-grupo').classList.remove('flex');
  }

  async function alternarHorario(id, ativo) {
    const resultado = await enviarComCsrf('index.php?pagina=admin-cronograma-horario-ativo', {
      cronograma_id: id,
      ativo: ativo ? '1' : '0'
    });
    if (resultado.sucesso) {
      toast(ativo ? 'Horário ativado' : 'Horário pausado');
      location.reload();
    } else {
      toast(resultado.mensagem || 'Não foi possível atualizar.', 'erro');
    }
  }

  function editarHorario(id) {
    // Buscar dados do horário pra preencher o formulário
    fetch('index.php?pagina=admin-cronograma&id=' + id)
      .then(r => r.text())
      .catch(() => {
        // Se não conseguir buscar, abre o modal vazio
        abrirModalEditarHorario(id, '08:00', 'diaria', '1,2,3,4,5', null);
      });
    
    // Por enquanto, abre com dados vazios - você pode melhorar buscando os dados via AJAX
    abrirModalEditarHorario(id, '08:00', 'diaria', '1,2,3,4,5', null);
  }

  function abrirModalEditarHorario(id, horario, frequencia, diasSemana, diaMes) {
    document.getElementById('form-editar-id').value = id;
    document.getElementById('form-editar-horario').value = horario;
    document.getElementById('form-editar-frequencia').value = frequencia;
    document.getElementById('form-editar-dia-mes').value = diaMes || '';
    
    // Desmarcar todos os checkboxes
    document.querySelectorAll('input[name="form-editar-dias[]"]').forEach(cb => cb.checked = false);
    
    // Marcar os dias corretos
    if (diasSemana) {
      diasSemana.split(',').forEach(dia => {
        const cb = document.querySelector('input[name="form-editar-dias[]"][value="' + dia + '"]');
        if (cb) cb.checked = true;
      });
    }
    
    // Mostrar/ocultar campo de dia do mês
    atualizarCamposDiaEditacao();
    
    document.getElementById('modal-editar-horario').classList.remove('hidden');
    document.getElementById('modal-editar-horario').classList.add('flex');
  }

  function atualizarCamposDiaEditacao() {
    const frequencia = document.getElementById('form-editar-frequencia').value;
    document.getElementById('form-editar-dia-mes-container').style.display = 
      frequencia === 'mensal' ? 'block' : 'none';
    document.getElementById('form-editar-dias-container').style.display = 
      frequencia === 'diaria' ? 'block' : 'none';
  }

  async function salvarEdicaoHorario(e) {
    e.preventDefault();
    
    const id = document.getElementById('form-editar-id').value;
    const horario = document.getElementById('form-editar-horario').value;
    const frequencia = document.getElementById('form-editar-frequencia').value;
    const diaMes = document.getElementById('form-editar-dia-mes').value;
    
    const diasMarcados = Array.from(document.querySelectorAll('input[name="form-editar-dias[]"]:checked'))
      .map(cb => cb.value);
    
    const dados = {
      id: id,
      horario: horario,
      frequencia: frequencia,
      dias_semana: diasMarcados,
      dia_mes: diaMes || '0',
      csrf_token: document.querySelector('input[name="csrf_token"]').value
    };
    
    const resultado = await fetch('index.php?pagina=admin-cronograma-horario-atualizar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams(dados)
    }).then(r => r.json()).catch(err => ({ sucesso: false, mensagem: 'Erro na requisição' }));
    
    if (resultado.sucesso) {
      toast('Horário atualizado com sucesso');
      setTimeout(() => location.reload(), 500);
    } else {
      toast(resultado.mensagem || 'Erro ao atualizar', 'erro');
    }
  }

  function fecharModalEditarHorario() {
    document.getElementById('modal-editar-horario').classList.add('hidden');
    document.getElementById('modal-editar-horario').classList.remove('flex');
  }

  async function deletarHorario(id) {
    if (!confirm('Tem certeza que quer deletar este horário?')) return;
    
    const resultado = await enviarComCsrf('index.php?pagina=admin-cronograma-horario-deletar', {
      cronograma_id: id
    });
    if (resultado.sucesso) {
      toast('Horário deletado');
      location.reload();
    } else {
      toast(resultado.mensagem || 'Não foi possível deletar.', 'erro');
    }
  }

  function abrirModalNovoHorario() {
    // Fecha o modal de gerenciar
    fecharModalGerenciar();
    // Abre o modal de novo agendamento
    document.getElementById('modal-novo-agendamento').classList.remove('hidden');
    document.getElementById('modal-novo-agendamento').classList.add('flex');
  }
</script>

<!-- MODAL EDITAR HORÁRIO -->
<div id="modal-editar-horario" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
  <div class="w-full max-w-md bg-elevated border border-bord rounded-2xl p-6">
    <h3 class="font-display font-semibold text-base mb-4">Editar Horário</h3>
    
    <form onsubmit="salvarEdicaoHorario(event)" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= Saida::e($_SESSION['csrf_token'] ?? '') ?>">
      <input type="hidden" id="form-editar-id">
      
      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Horário</label>
        <input type="time" id="form-editar-horario" required class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
      </div>

      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Frequência</label>
        <select id="form-editar-frequencia" onchange="atualizarCamposDiaEditacao()" required class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
          <option value="diaria">Diária (todos os dias selecionados)</option>
          <option value="mensal">Mensal (dia específico do mês)</option>
        </select>
      </div>

      <div id="form-editar-dias-container">
        <label class="block text-xs font-medium text-tsecondary mb-2">Dias da Semana</label>
        <div class="grid grid-cols-2 gap-2">
          <label class="flex items-center gap-2 text-xs">
            <input type="checkbox" name="form-editar-dias[]" value="1"> Seg
          </label>
          <label class="flex items-center gap-2 text-xs">
            <input type="checkbox" name="form-editar-dias[]" value="2"> Ter
          </label>
          <label class="flex items-center gap-2 text-xs">
            <input type="checkbox" name="form-editar-dias[]" value="3"> Qua
          </label>
          <label class="flex items-center gap-2 text-xs">
            <input type="checkbox" name="form-editar-dias[]" value="4"> Qui
          </label>
          <label class="flex items-center gap-2 text-xs">
            <input type="checkbox" name="form-editar-dias[]" value="5"> Sex
          </label>
          <label class="flex items-center gap-2 text-xs">
            <input type="checkbox" name="form-editar-dias[]" value="6"> Sáb
          </label>
          <label class="flex items-center gap-2 text-xs">
            <input type="checkbox" name="form-editar-dias[]" value="7"> Dom
          </label>
        </div>
      </div>

      <div id="form-editar-dia-mes-container" style="display: none;">
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Dia do Mês (1-31)</label>
        <input type="number" id="form-editar-dia-mes" min="1" max="31" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
      </div>

      <div class="flex gap-2 pt-4 border-t border-bordsoft">
        <button type="button" onclick="fecharModalEditarHorario()" class="flex-1 border border-bord rounded-lg py-2.5 text-sm font-medium hover:bg-surface transition">Cancelar</button>
        <button type="submit" class="flex-1 grad-flow text-[#04342C] font-semibold rounded-lg py-2.5 text-sm">Salvar</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../partials/rodape.php'; ?>