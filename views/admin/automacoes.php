<?php
/**
 * views/admin/automacoes.php
 * Renderizado por AdminAutomacaoController::tela()
 * Espera $automacoes
 */

use Udflow\util\Saida;

$paginaAtiva = 'admin-automacoes';
$tituloPagina = 'Administração · Automações';
require __DIR__ . '/../partials/cabecalho.php';

$icones = [
    'kpi' => '<path d="M4 20V10M11 20V4M18 20v-7"/>',
    'mao_obra_batida' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
    'estadia' => '<rect x="1" y="8" width="12" height="8" rx="1"/><path d="M13 11h4l3 3v2h-7z"/><circle cx="5.5" cy="17.5" r="1.6"/><circle cx="16.5" cy="17.5" r="1.6"/>',
];
?>

<h1 class="font-display font-semibold text-xl mb-1">Automações</h1>
<p class="text-tsecondary text-sm mb-6">Controla o que aparece na sidebar de cada pessoa. "Visível pra usuários" é a chave que hoje mantém Mão de Obra Batida e Estadia restritas a admins.</p>

<div class="space-y-3">
  <?php foreach ($automacoes as $automacao): ?>
    <div class="bg-surface border border-bord rounded-xl p-4 flex items-center gap-4">
      <div class="w-10 h-10 rounded-lg bg-flow/10 flex items-center justify-center shrink-0">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1FD8C4" stroke-width="1.8"><?= $icones[$automacao['chave']] ?? '<circle cx="12" cy="12" r="9"/>' ?></svg>
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
</script>

<?php require __DIR__ . '/../partials/rodape.php'; ?>
