<?php
/**
 * views/admin/automacoes.php
 * Renderizado por AdminAutomacaoController::tela()
 * Espera $automacoes
 */

use Udflow\util\Saida;
use Udflow\util\Csrf;

$paginaAtiva = 'admin-automacoes';
$tituloPagina = 'Administração · Automações';
require __DIR__ . '/../partials/cabecalho.php';

?>

<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="font-display font-semibold text-xl mb-1">Automações</h1>
    <p class="text-tsecondary text-sm">Controla o que aparece na sidebar de cada pessoa. "Visível pra usuários" é a chave que hoje mantém Programação semanal e Estadia restritas a admins.</p>
  </div>
  <button onclick="abrirModalCriarAutomacao()" class="grad-flow text-[#04342C] font-semibold text-sm rounded-lg px-4 py-2.5 flex items-center gap-2">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
      <path d="M12 5v14M5 12h14" />
    </svg>
    Nova Automação
  </button>
</div>

<div class="space-y-3">
  <?php foreach ($automacoes as $automacao): ?>
    <div class="bg-surface border border-bord rounded-xl p-4 flex items-center gap-4">
      <div class="w-10 h-10 rounded-lg bg-flow/10 flex items-center justify-center shrink-0">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1FD8C4" stroke-width="1.8"><?= $automacao['icon_svg'] ?: '<circle cx="12" cy="12" r="9"/>' ?></svg>
      </div>
      <div class="flex-1 min-w-0">
        <p class="font-medium text-sm"><?= Saida::e($automacao['nome']) ?></p>
        <p class="text-tmuted text-xs font-mono"><?= Saida::e($automacao['rota']) ?></p>
      </div>
      <?php if ($automacao['possui_agendamento']): ?>
        <span class="text-[10px] bg-info/10 text-info px-2 py-1 rounded-full font-medium">Tem agendamento</span>
      <?php endif; ?>
      <label class="flex items-center gap-2 text-xs text-tsecondary cursor-pointer">
        Visível pra usuários
        <input type="checkbox" onchange="alternarVisibilidade(<?= (int) $automacao['id'] ?>, this.checked)" <?= $automacao['visivel_para_usuarios'] ? 'checked' : '' ?> class="rounded border-bord">
      </label>
    </div>
  <?php endforeach; ?>
</div>

<script>
  async function alternarVisibilidade(automacaoId, visivel) {
    const resultado = await enviarComCsrf('index.php?pagina=admin-automacoes-visibilidade', { automacao_id: automacaoId, visivel: visivel ? '1' : '0' });
    if (resultado.sucesso) {
      toast(visivel ? 'Liberada pra usuários comuns' : 'Restrita a admins novamente');
    } else {
      toast(resultado.mensagem || 'Não foi possível atualizar.', 'erro');
    }
  }

  function abrirModalCriarAutomacao() {
    document.getElementById('modal-criar-automacao').classList.remove('hidden');
    document.getElementById('modal-criar-automacao').classList.add('flex');
  }

  async function salvarNovaAutomacao(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const resultado = await enviarComCsrf('index.php?pagina=admin-automacoes-criar', Object.fromEntries(formData));
    
    if (resultado.sucesso) {
      toast('Automação criada com sucesso! Recarregando...');
      setTimeout(() => location.reload(), 800);
    } else {
      toast(resultado.mensagem || 'Erro ao criar', 'erro');
    }
  }
</script>

<!-- MODAL CRIAR AUTOMAÇÃO -->
<div id="modal-criar-automacao" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
  <div class="w-full max-w-2xl bg-elevated border border-bord rounded-2xl p-6">
    <h3 class="font-display font-semibold text-lg mb-4">Criar Nova Automação</h3>
    
    <form onsubmit="salvarNovaAutomacao(event)" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= Saida::e($_SESSION['csrf_token'] ?? '') ?>">
      
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-tsecondary mb-1.5">Nome da Automação</label>
          <input type="text" name="nome" placeholder="ex: KPI Relatórios" required class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
          <p class="text-[10px] text-tmuted mt-1">Nome que aparece na sidebar e admin</p>
        </div>

        <div>
          <label class="block text-xs font-medium text-tsecondary mb-1.5">Chave (identificador)</label>
          <input type="text" name="chave" placeholder="ex: kpi_relatorio" pattern="[a-z_]+" required class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus font-mono">
          <p class="text-[10px] text-tmuted mt-1">Só letras minúsculas e underscore</p>
        </div>
      </div>

      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Rota (URL amigável)</label>
        <div class="flex items-stretch">
          <span class="flex items-center px-3 rounded-l-lg border border-r-0 border-bord bg-elevated text-tmuted text-sm font-mono">/automacoes/</span>
          <input type="text" name="rota" placeholder="status-ar" pattern="[a-z-]+" required class="w-full bg-surface border border-bord rounded-l-none rounded-r-lg px-3 py-2.5 text-sm glow-focus font-mono">
        </div>
        <p class="text-[10px] text-tmuted mt-1">Só letras minúsculas e hífen - o "/automacoes/" já vem incluso, não precisa digitar</p>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-tsecondary mb-1.5">Webhook URL</label>
          <input type="text" name="webhook_url" placeholder="https://n8n.udlog.online/webhook/..." class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus font-mono text-[11px]">
        </div>

        <div>
          <label class="block text-xs font-medium text-tsecondary mb-1.5">Método HTTP</label>
          <select name="webhook_metodo" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
            <option value="POST">POST</option>
            <option value="GET">GET</option>
            <option value="PUT">PUT</option>
            <option value="PATCH">PATCH</option>
            <option value="DELETE">DELETE</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Ícone (SVG)</label>
        <input type="text" name="icon_svg" placeholder='ex: &lt;path d="M4 20V10M11 20V4M18 20v-7"/&gt;' class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus font-mono text-[11px]">
        <p class="text-[10px] text-tmuted mt-1">Conteúdo interno de um &lt;svg viewBox="0 0 24 24"&gt; (path/circle/rect) - aparece na sidebar e no menu de configuração. Deixa em branco pra usar o ícone genérico.</p>
      </div>

      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-medium text-tsecondary mb-1.5">Posição (ordem)</label>
          <input type="number" name="posicao" value="10" min="1" max="9999" step="10" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
          <p class="text-[10px] text-tmuted mt-1">Controla a ordem na sidebar - use passos de 10 (10, 20, 30...) pra sobrar espaço de inserir no meio depois</p>
        </div>

        <div>
          <label class="flex items-center gap-2 text-xs font-medium text-tsecondary mt-6">
            <input type="checkbox" name="possui_agendamento" value="1" class="rounded border-bord">
            Tem agendamento
          </label>
        </div>

        <div>
          <label class="flex items-center gap-2 text-xs font-medium text-tsecondary mt-6">
            <input type="checkbox" name="visivel_para_usuarios" value="1" class="rounded border-bord" checked>
            Visível pra usuários
          </label>
        </div>
      </div>

      <div class="flex gap-2 pt-4 border-t border-bordsoft">
        <button type="button" onclick="document.getElementById('modal-criar-automacao').classList.add('hidden')" class="flex-1 border border-bord rounded-lg py-2.5 text-sm font-medium hover:bg-surface transition">Cancelar</button>
        <button type="submit" class="flex-1 grad-flow text-[#04342C] font-semibold rounded-lg py-2.5 text-sm">Criar Automação</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../partials/rodape.php'; ?>