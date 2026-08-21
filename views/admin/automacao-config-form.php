<?php

/**
 * views/admin/automacao-config-form.php
 * 
 * Renderizado por AutomacaoConfigController::editar()
 * Espera $automacao, $config (com campos, regras, headers)
 */

use Udflow\util\Saida;
use Udflow\util\Csrf;

$paginaAtiva = 'admin-automacao-config';
$tituloPagina = 'Configurar: ' . ($automacao['nome'] ?? 'Automação desconhecida');
require __DIR__ . '/../partials/cabecalho.php';

$campos = $config['campos'] ?? [];
$regras = $config['regras'] ?? [];
$headers = $config['headers'] ?? [];

// Extrair nomes dos campos já salvos para marcar os checkboxes
$camposSalvos = array_map(fn($c) => $c['nome_campo'] ?? null, $campos);
$camposSalvos = array_filter($camposSalvos); // Remove nulls

$tiposDado = ['string', 'integer', 'decimal', 'boolean', 'email', 'timestamp', 'uuid', 'json', 'array', 'date', 'time'];
$tiposRegra = ['fixed_value', 'map_from_banco', 'timestamp', 'uuid', 'expression', 'concatenate', 'if_condition'];
?>

<div class="flex items-center justify-between mb-1">
  <h1 class="font-display font-semibold text-xl"><?= Saida::e($automacao['nome']) ?></h1>
  <a href="index.php?pagina=admin-automacao-config" class="text-xs text-flow hover:underline">
    ← Voltar
  </a>
</div>
<p class="text-tsecondary text-sm mb-6">Configure webhook, campos do payload, regras de transformação e headers.</p>

<!-- ========================================================================
     WEBHOOK BÁSICO
     ======================================================================== -->

<div class="bg-surface border border-bord rounded-2xl p-6 mb-6">
  <h2 class="font-display font-semibold text-base mb-4">⚙️ Configuração Básica do Webhook</h2>
  
  <form onsubmit="salvarConfigBasica(event, <?= intval($automacao['id'] ?? 0) ?>)" class="space-y-4">
    <input type="hidden" name="csrf_token" value="<?= Saida::e($_SESSION['csrf_token'] ?? '') ?>">
    <input type="hidden" name="automacao_id" value="<?= intval($automacao['id'] ?? 0) ?>">
    
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">URL do Webhook</label>
        <input type="url" name="webhook_url" value="<?= Saida::e($automacao['webhook_url'] ?? '') ?>" 
          class="w-full bg-elevated border border-bordsoft rounded-lg px-3 py-2.5 text-sm glow-focus" 
          placeholder="https://...">
      </div>
      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Método HTTP</label>
        <select name="webhook_metodo" class="w-full bg-elevated border border-bordsoft rounded-lg px-3 py-2.5 text-sm glow-focus">
          <option value="GET" <?= ($automacao['webhook_metodo'] ?? 'POST') === 'GET' ? 'selected' : '' ?>>GET</option>
          <option value="POST" <?= ($automacao['webhook_metodo'] ?? 'POST') === 'POST' ? 'selected' : '' ?>>POST</option>
          <option value="PUT" <?= ($automacao['webhook_metodo'] ?? 'POST') === 'PUT' ? 'selected' : '' ?>>PUT</option>
          <option value="PATCH" <?= ($automacao['webhook_metodo'] ?? 'POST') === 'PATCH' ? 'selected' : '' ?>>PATCH</option>
        </select>
      </div>
    </div>

    <div class="flex gap-2 pt-2">
      <button type="submit" class="grad-flow text-[#04342C] font-semibold rounded-lg px-4 py-2 text-sm">
        Salvar
      </button>
    </div>
  </form>

  <p class="text-[10px] text-tmuted mt-3">
    ℹ️ Configure URL e método do webhook aqui. Os dados serão enviados neste endereço.
  </p>
</div>

<!-- ========================================================================
     CAMPOS DO PAYLOAD
     ======================================================================== -->

<div class="bg-surface border border-bord rounded-2xl p-6 mb-6">
  <h2 class="font-display font-semibold text-base mb-4">📋 Campos do Payload</h2>
  
  <form onsubmit="salvarCamposSelecionados(event)" class="space-y-6">
    <input type="hidden" name="csrf_token" value="<?= Saida::e($_SESSION['csrf_token'] ?? '') ?>">
    <input type="hidden" name="automacao_id" value="<?= intval($automacao['id'] ?? 0) ?>">

    <p class="text-xs text-tsecondary mb-4">Selecione quais campos serão enviados no webhook. Você pode marcar vários de uma vez.</p>

    <!-- Campos de Execução -->
    <div class="border border-bordsoft rounded-lg p-4">
      <h3 class="text-sm font-semibold text-flow mb-3">⚡ Campos de Execução</h3>
      <div class="space-y-2">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="campos[]" value="execucaoId" class="w-4 h-4 rounded border-bord bg-surface" <?= in_array('execucaoId', $camposSalvos) ? 'checked' : '' ?>>
          <span class="text-sm">execucaoId</span>
          <span class="text-[10px] text-tmuted">(integer)</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="campos[]" value="emailDestino" class="w-4 h-4 rounded border-bord bg-surface" <?= in_array('emailDestino', $camposSalvos) ? 'checked' : '' ?>>
          <span class="text-sm">emailDestino</span>
          <span class="text-[10px] text-tmuted">(email)</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="campos[]" value="modo" class="w-4 h-4 rounded border-bord bg-surface" <?= in_array('modo', $camposSalvos) ? 'checked' : '' ?>>
          <span class="text-sm">modo</span>
          <span class="text-[10px] text-tmuted">(string: AUTOMATICO | MANUAL)</span>
        </label>
      </div>
    </div>

    <!-- Dados do Cliente -->
    <div class="border border-bordsoft rounded-lg p-4">
      <h3 class="text-sm font-semibold text-flow mb-3">👤 Dados do Cliente (tb_clientes)</h3>
      <div class="space-y-2">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="campos[]" value="codigo_cliente" class="w-4 h-4 rounded border-bord bg-surface" <?= in_array('codigo_cliente', $camposSalvos) ? 'checked' : '' ?>>
          <span class="text-sm">codigo_cliente</span>
          <span class="text-[10px] text-tmuted">(string)</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="campos[]" value="codigo_talent" class="w-4 h-4 rounded border-bord bg-surface" <?= in_array('codigo_talent', $camposSalvos) ? 'checked' : '' ?>>
          <span class="text-sm">codigo_talent</span>
          <span class="text-[10px] text-tmuted">(integer)</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="campos[]" value="razao_social" class="w-4 h-4 rounded border-bord bg-surface" <?= in_array('razao_social', $camposSalvos) ? 'checked' : '' ?>>
          <span class="text-sm">razao_social</span>
          <span class="text-[10px] text-tmuted">(string)</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="campos[]" value="nome_exibicao" class="w-4 h-4 rounded border-bord bg-surface" <?= in_array('nome_exibicao', $camposSalvos) ? 'checked' : '' ?>>
          <span class="text-sm">nome_exibicao</span>
          <span class="text-[10px] text-tmuted">(string)</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="campos[]" value="cnpj" class="w-4 h-4 rounded border-bord bg-surface" <?= in_array('cnpj', $camposSalvos) ? 'checked' : '' ?>>
          <span class="text-sm">cnpj</span>
          <span class="text-[10px] text-tmuted">(string)</span>
        </label>
      </div>
    </div>

    <!-- Dados da Unidade -->
    <div class="border border-bordsoft rounded-lg p-4">
      <h3 class="text-sm font-semibold text-flow mb-3">🏢 Unidade (tb_unidades)</h3>
      <div class="space-y-2">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="campos[]" value="unidade_codigo" class="w-4 h-4 rounded border-bord bg-surface" <?= in_array('unidade_codigo', $camposSalvos) ? 'checked' : '' ?>>
          <span class="text-sm">unidade_codigo</span>
          <span class="text-[10px] text-tmuted">(string: Mauá I, Mauá II, etc)</span>
        </label>
      </div>
    </div>

    <!-- Configuração Visual -->
    <div class="border border-bordsoft rounded-lg p-4">
      <h3 class="text-sm font-semibold text-flow mb-3">🎨 Configuração Visual (tb_clientes_config)</h3>
      <div class="space-y-2">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="campos[]" value="logo_url" class="w-4 h-4 rounded border-bord bg-surface" <?= in_array('logo_url', $camposSalvos) ? 'checked' : '' ?>>
          <span class="text-sm">logo_url</span>
          <span class="text-[10px] text-tmuted">(string: URL)</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="campos[]" value="cor_primaria" class="w-4 h-4 rounded border-bord bg-surface" <?= in_array('cor_primaria', $camposSalvos) ? 'checked' : '' ?>>
          <span class="text-sm">cor_primaria</span>
          <span class="text-[10px] text-tmuted">(string: hex color)</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="campos[]" value="cor_secundaria" class="w-4 h-4 rounded border-bord bg-surface" <?= in_array('cor_secundaria', $camposSalvos) ? 'checked' : '' ?>>
          <span class="text-sm">cor_secundaria</span>
          <span class="text-[10px] text-tmuted">(string: hex color)</span>
        </label>
      </div>
    </div>

    <div class="flex gap-2 pt-4 border-t border-bordsoft">
      <button type="submit" class="grad-flow text-[#04342C] font-semibold rounded-lg px-4 py-2 text-sm">
        Salvar Seleção
      </button>
    </div>
  </form>
</div>

<!-- ========================================================================
     REGRAS DE TRANSFORMAÇÃO
     ======================================================================== -->

<div class="bg-surface border border-bord rounded-2xl p-6 mb-6">
  <div class="flex items-center justify-between mb-4">
    <h2 class="font-display font-semibold text-base">🔄 Regras de Transformação</h2>
    <button onclick="abrirModalRegra()" class="grad-flow text-[#04342C] font-semibold text-xs rounded-lg px-3 py-2 flex items-center gap-1.5">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
        <path d="M12 5v14M5 12h14" />
      </svg>Adicionar regra
    </button>
  </div>

  <p class="text-xs text-tmuted mb-4">
    As regras transformam valores: buscam do banco, geram UUIDs, timestamps, executam expressões PHP, etc.
  </p>

  <div id="lista-regras" class="space-y-2">
    <?php if (empty($regras)): ?>
      <p class="text-tmuted text-xs italic py-6 text-center">Nenhuma regra ainda.</p>
    <?php else: ?>
      <?php foreach ($regras as $regra): ?>
        <div class="flex items-center justify-between p-3 bg-elevated border border-bordsoft rounded-lg">
          <div class="flex-1">
            <p class="text-sm font-semibold">
              <span class="text-blue-500"><?= Saida::e($regra['tipo_regra']) ?></span>
              <?php if (!$regra['ativo']): ?>
                <span class="text-[10px] text-tmuted ml-2">(desativa)</span>
              <?php endif; ?>
            </p>
          </div>
          <div class="flex gap-2">
            <button onclick="editarRegra(<?= (int) $regra['id'] ?>)" class="text-xs text-flow hover:underline">Editar</button>
            <button onclick="deletarRegra(<?= (int) $regra['id'] ?>)" class="text-xs text-red-500 hover:underline">Deletar</button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ========================================================================
     HEADERS CUSTOMIZÁVEIS
     ======================================================================== -->

<div class="bg-surface border border-bord rounded-2xl p-6 mb-6">
  <div class="flex items-center justify-between mb-4">
    <h2 class="font-display font-semibold text-base">📨 Headers HTTP Customizáveis</h2>
    <button onclick="abrirModalHeader()" class="grad-flow text-[#04342C] font-semibold text-xs rounded-lg px-3 py-2 flex items-center gap-1.5">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
        <path d="M12 5v14M5 12h14" />
      </svg>Adicionar header
    </button>
  </div>

  <div id="lista-headers" class="space-y-2">
    <?php if (empty($headers)): ?>
      <p class="text-tmuted text-xs italic py-6 text-center">Nenhum header customizado.</p>
    <?php else: ?>
      <?php foreach ($headers as $header): ?>
        <div class="flex items-center justify-between p-3 bg-elevated border border-bordsoft rounded-lg">
          <div class="flex-1">
            <p class="text-sm font-mono font-semibold text-purple-500"><?= Saida::e($header['chave']) ?></p>
            <p class="text-xs text-tmuted mt-0.5">
              <?= Saida::e(substr($header['valor'], 0, 50)) ?>
              <?php if (strlen($header['valor']) > 50): ?>...<?php endif; ?>
            </p>
          </div>
          <div class="flex gap-2">
            <button onclick="editarHeader(<?= (int) $header['id'] ?>)" class="text-xs text-flow hover:underline">Editar</button>
            <button onclick="deletarHeader(<?= (int) $header['id'] ?>)" class="text-xs text-red-500 hover:underline">Deletar</button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ========================================================================
     LOGS E ESTATÍSTICAS
     ======================================================================== -->

<div class="bg-surface border border-bord rounded-2xl p-6 mb-6">
  <div class="flex items-center justify-between mb-4">
    <h2 class="font-display font-semibold text-base">📊 Logs de Execução</h2>
    <button onclick="carregarLogs(<?= intval($automacao['id'] ?? 0) ?>)" class="text-xs text-flow hover:underline">Atualizar</button>
  </div>

  <div id="estatisticas" class="grid grid-cols-4 gap-3 mb-4">
    <div class="bg-elevated border border-bordsoft rounded-lg p-3">
      <p class="text-xs text-tmuted">Total</p>
      <p class="text-lg font-semibold text-flow" id="stat-total">—</p>
    </div>
    <div class="bg-elevated border border-bordsoft rounded-lg p-3">
      <p class="text-xs text-tmuted">Sucessos</p>
      <p class="text-lg font-semibold text-green-500" id="stat-sucessos">—</p>
    </div>
    <div class="bg-elevated border border-bordsoft rounded-lg p-3">
      <p class="text-xs text-tmuted">Erros</p>
      <p class="text-lg font-semibold text-red-500" id="stat-erros">—</p>
    </div>
    <div class="bg-elevated border border-bordsoft rounded-lg p-3">
      <p class="text-xs text-tmuted">Tempo Médio</p>
      <p class="text-lg font-semibold text-blue-500" id="stat-tempo">—</p>
    </div>
  </div>

  <div id="logs-container" class="text-center text-tmuted text-xs py-4">
    Carregando logs...
  </div>
</div>

<!-- ========================================================================
     MODAIS
     ======================================================================== -->

<!-- MODAL CAMPO -->
<div id="modal-campo" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
  <div class="w-full max-w-md bg-elevated border border-bord rounded-2xl p-6">
    <h3 class="font-display font-semibold text-base mb-4">Adicionar Campo</h3>
    
    <form onsubmit="salvarCampo(event)" class="space-y-3">
      <input type="hidden" name="csrf_token" value="<?= Saida::e($_SESSION['csrf_token'] ?? '') ?>">
      <input type="hidden" name="automacao_id" value="<?= intval($automacao['id'] ?? 0) ?>">
      
      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Nome do Campo</label>
        <input type="text" name="nome_campo" placeholder="ex: cliente_id" required class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
      </div>

      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Tipo de Dado</label>
        <select name="tipo_dado" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
          <?php foreach ($tiposDado as $tipo): ?>
            <option value="<?= $tipo ?>"><?= $tipo ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="flex items-center gap-2">
          <input type="checkbox" name="obrigatorio" value="1" class="rounded border-bord">
          <span class="text-xs font-medium text-tsecondary">Campo obrigatório</span>
        </label>
      </div>

      <div class="flex gap-2 pt-2">
        <button type="button" onclick="document.getElementById('modal-campo').classList.add('hidden')" class="flex-1 border border-bord rounded-lg py-2.5 text-sm font-medium hover:bg-surface transition">Cancelar</button>
        <button type="submit" class="flex-1 grad-flow text-[#04342C] font-semibold rounded-lg py-2.5 text-sm">Criar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL REGRA -->
<div id="modal-regra" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
  <div class="w-full max-w-md bg-elevated border border-bord rounded-2xl p-6">
    <h3 class="font-display font-semibold text-base mb-4">Adicionar Regra</h3>
    
    <form onsubmit="salvarRegra(event)" class="space-y-3">
      <input type="hidden" name="csrf_token" value="<?= Saida::e($_SESSION['csrf_token'] ?? '') ?>">
      <input type="hidden" name="automacao_id" value="<?= intval($automacao['id'] ?? 0) ?>">
      
      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Tipo de Regra</label>
        <select name="tipo_regra" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus" required>
          <?php foreach ($tiposRegra as $tipo): ?>
            <option value="<?= $tipo ?>"><?= $tipo ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="flex items-center gap-2">
          <input type="checkbox" name="ativo" value="1" class="rounded border-bord" checked>
          <span class="text-xs font-medium text-tsecondary">Regra ativa</span>
        </label>
      </div>

      <div class="flex gap-2 pt-2">
        <button type="button" onclick="document.getElementById('modal-regra').classList.add('hidden')" class="flex-1 border border-bord rounded-lg py-2.5 text-sm font-medium hover:bg-surface transition">Cancelar</button>
        <button type="submit" class="flex-1 grad-flow text-[#04342C] font-semibold rounded-lg py-2.5 text-sm">Criar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL HEADER -->
<div id="modal-header" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
  <div class="w-full max-w-md bg-elevated border border-bord rounded-2xl p-6">
    <h3 class="font-display font-semibold text-base mb-4">Adicionar Header</h3>
    
    <form onsubmit="salvarHeader(event)" class="space-y-3">
      <input type="hidden" name="csrf_token" value="<?= Saida::e($_SESSION['csrf_token'] ?? '') ?>">
      <input type="hidden" name="automacao_id" value="<?= intval($automacao['id'] ?? 0) ?>">
      
      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Chave do Header</label>
        <input type="text" name="chave" placeholder="ex: Authorization" required class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
      </div>

      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Valor</label>
        <input type="text" name="valor" placeholder="ex: Bearer token123" required class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
      </div>

      <div class="flex gap-2 pt-2">
        <button type="button" onclick="document.getElementById('modal-header').classList.add('hidden')" class="flex-1 border border-bord rounded-lg py-2.5 text-sm font-medium hover:bg-surface transition">Cancelar</button>
        <button type="submit" class="flex-1 grad-flow text-[#04342C] font-semibold rounded-lg py-2.5 text-sm">Criar</button>
      </div>
    </form>
  </div>
</div>

<script>
console.log('PAGE LOAD: $automacao[id] from PHP =', <?= intval($automacao['id'] ?? 0) ?>);

function abrirModalCampo() {
  document.getElementById('modal-campo').classList.remove('hidden');
  document.getElementById('modal-campo').classList.add('flex');
}

function abrirModalRegra() {
  document.getElementById('modal-regra').classList.remove('hidden');
  document.getElementById('modal-regra').classList.add('flex');
}

function abrirModalHeader() {
  document.getElementById('modal-header').classList.remove('hidden');
  document.getElementById('modal-header').classList.add('flex');
}

async function salvarCampo(e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  const resultado = await enviarComCsrf('index.php?pagina=admin-automacao-config-campo-criar', Object.fromEntries(formData));
  
  if (resultado.sucesso) {
    toast('Campo criado com sucesso');
    location.reload();
  } else {
    toast(resultado.mensagem || 'Erro ao criar campo', 'erro');
  }
}

async function salvarRegra(e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  const resultado = await enviarComCsrf('index.php?pagina=admin-automacao-config-regra-criar', Object.fromEntries(formData));
  
  if (resultado.sucesso) {
    toast('Regra criada com sucesso');
    location.reload();
  } else {
    toast(resultado.mensagem || 'Erro ao criar regra', 'erro');
  }
}

async function salvarHeader(e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  const resultado = await enviarComCsrf('index.php?pagina=admin-automacao-config-header-criar', Object.fromEntries(formData));
  
  if (resultado.sucesso) {
    toast('Header criado com sucesso');
    location.reload();
  } else {
    toast(resultado.mensagem || 'Erro ao criar header', 'erro');
  }
}

async function deletarCampo(id) {
  if (!confirm('Tem certeza que quer deletar este campo?')) return;
  
  const resultado = await enviarComCsrf('index.php?pagina=admin-automacao-config-campo-deletar', {
    campo_id: id,
    csrf_token: document.querySelector('input[name="csrf_token"]').value
  });
  
  if (resultado.sucesso) {
    toast('Campo deletado');
    location.reload();
  } else {
    toast(resultado.mensagem || 'Erro ao deletar', 'erro');
  }
}

async function deletarRegra(id) {
  if (!confirm('Tem certeza que quer deletar esta regra?')) return;
  
  const resultado = await enviarComCsrf('index.php?pagina=admin-automacao-config-regra-deletar', {
    regra_id: id,
    csrf_token: document.querySelector('input[name="csrf_token"]').value
  });
  
  if (resultado.sucesso) {
    toast('Regra deletada');
    location.reload();
  } else {
    toast(resultado.mensagem || 'Erro ao deletar', 'erro');
  }
}

async function deletarHeader(id) {
  if (!confirm('Tem certeza que quer deletar este header?')) return;
  
  const resultado = await enviarComCsrf('index.php?pagina=admin-automacao-config-header-deletar', {
    header_id: id,
    csrf_token: document.querySelector('input[name="csrf_token"]').value
  });
  
  if (resultado.sucesso) {
    toast('Header deletado');
    location.reload();
  } else {
    toast(resultado.mensagem || 'Erro ao deletar', 'erro');
  }
}

async function carregarLogs(automacaoId) {
  const resultado = await fetch(`index.php?pagina=admin-automacao-config-logs&automacao_id=${automacaoId}`).then(r => r.json());
  
  if (resultado.sucesso) {
    const stats = resultado.estatisticas;
    document.getElementById('stat-total').textContent = stats.total_execucoes || '0';
    document.getElementById('stat-sucessos').textContent = stats.execucoes_sucesso || '0';
    document.getElementById('stat-erros').textContent = stats.execucoes_erro || '0';
    document.getElementById('stat-tempo').textContent = (stats.tempo_medio_ms || 0).toFixed(0) + 'ms';
    
    const logsHtml = resultado.logs.length > 0 
      ? resultado.logs.slice(0, 5).map(log => `
          <div class="text-left p-2 bg-elevated border border-bordsoft rounded text-[10px]">
            <p class="font-mono text-flow">${log.metodo_http} ${log.http_status}</p>
            <p class="text-tmuted">${new Date(log.criado_em).toLocaleString('pt-BR')}</p>
          </div>
        `).join('')
      : '<p class="text-tmuted text-xs">Nenhuma execução ainda</p>';
    
    document.getElementById('logs-container').innerHTML = logsHtml;
  }
}

async function salvarConfigBasica(e, automacaoId) {
  e.preventDefault();
  
  const url = document.querySelector('input[name="webhook_url"]').value;
  const metodo = document.querySelector('select[name="webhook_metodo"]').value;
  const csrf = document.querySelector('input[name="csrf_token"]').value;
  
  if (!url) {
    toast('Informe a URL do webhook', 'erro');
    return;
  }
  
  const resultado = await fetch('index.php?pagina=admin-automacao-config-webhook', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      automacao_id: automacaoId,
      webhook_url: url,
      webhook_metodo: metodo,
      csrf_token: csrf
    })
  }).then(r => r.json()).catch(err => ({ sucesso: false, mensagem: 'Erro na requisição' }));
  
  if (resultado.sucesso) {
    toast('Webhook salvo com sucesso');
  } else {
    toast(resultado.mensagem || 'Erro ao salvar', 'erro');
  }
}

async function salvarCamposSelecionados(e) {
  e.preventDefault();
  
  const formData = new FormData(e.target);
  const automacaoId = parseInt(formData.get('automacao_id'));
  const campos = formData.getAll('campos[]');
  const csrf = formData.get('csrf_token');

  if (automacaoId <= 0) {
    toast('Automação inválida', 'erro');
    return;
  }
  
  if (campos.length === 0) {
    toast('Selecione pelo menos um campo', 'erro');
    return;
  }
  
  const resultado = await fetch('index.php?pagina=admin-automacao-config-campos-salvar', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      automacao_id: automacaoId,
      campos: JSON.stringify(campos),
      csrf_token: csrf
    })
  }).then(r => r.json()).catch(err => ({ sucesso: false, mensagem: 'Erro na requisição' }));

  if (resultado.sucesso) {
    toast('Campos salvos com sucesso');
    setTimeout(() => location.reload(), 1000);
  } else {
    toast(resultado.mensagem || 'Erro ao salvar', 'erro');
  }
}

// Carregar logs ao abrir a página
carregarLogs(<?= intval($automacao['id'] ?? 0) ?>);
</script>

<?php require __DIR__ . '/../partials/rodape.php'; ?>