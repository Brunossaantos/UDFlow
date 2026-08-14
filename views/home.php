<?php
/**
 * views/home.php
 * Renderizado por HomeController::tela()
 * Espera $automacoes (array vindo de PermissaoDao::automacoesVisiveisParaUsuario)
 */

use Udflow\util\Saida;

$paginaAtiva = 'home';
$tituloPagina = 'Início';
require __DIR__ . '/partials/cabecalho.php';

$icones = [
    'kpi' => '<path d="M4 20V10M11 20V4M18 20v-7"/>',
    'mao_obra_batida' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
    'estadia' => '<rect x="1" y="8" width="12" height="8" rx="1"/><path d="M13 11h4l3 3v2h-7z"/><circle cx="5.5" cy="17.5" r="1.6"/><circle cx="16.5" cy="17.5" r="1.6"/>',
];

$hora = (int) date('H');
$saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
$nome = $_SESSION['usuario']['nome'] ?? '';
?>

<div class="rounded-2xl border border-bord bg-gradient-to-br from-surface to-elevated p-8 mb-8 relative overflow-hidden">
  <p class="text-tsecondary text-sm mb-1"><?= Saida::e($saudacao) ?>,</p>
  <h1 class="font-display font-semibold text-3xl mb-3"><?= Saida::e($nome) ?> 👋</h1>
  <p class="text-tsecondary text-sm max-w-md">Aqui você encontra as automações que a UDLOG disponibilizou pra você — dispare execuções, acompanhe status e gerencie sem sair de um único lugar.</p>
</div>

<p class="text-xs font-semibold tracking-widest text-tmuted mb-3">SUAS AUTOMAÇÕES</p>

<?php if (empty($automacoes)): ?>
  <div class="bg-surface/40 border border-dashed border-bord rounded-xl p-6 text-center text-tmuted text-sm">
    Você ainda não tem acesso a nenhuma automação. Fala com um administrador pra liberar.
  </div>
<?php else: ?>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <?php foreach ($automacoes as $automacao): ?>
      <div class="bg-surface border border-bord rounded-xl p-5 hover:border-flow/40 transition group">
        <div class="flex items-start justify-between mb-4">
          <div class="w-10 h-10 rounded-lg bg-flow/10 flex items-center justify-center">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#1FD8C4" stroke-width="1.8"><?= $icones[$automacao['chave']] ?? '<circle cx="12" cy="12" r="9"/>' ?></svg>
          </div>
          <span class="text-[10px] <?= $automacao['papel_efetivo'] === 'admin' ? 'bg-amber/10 text-amber' : 'bg-success/10 text-success' ?> px-2 py-0.5 rounded-full font-medium">
            <?= $automacao['papel_efetivo'] === 'admin' ? 'Admin' : 'Ativa' ?>
          </span>
        </div>
        <h3 class="font-display font-semibold text-[15px] mb-1"><?= Saida::e($automacao['nome']) ?></h3>
        <a href="index.php?pagina=<?= Saida::e(str_replace('_', '-', $automacao['chave'])) ?>" class="text-xs font-medium text-flow group-hover:gap-2 flex items-center gap-1.5 transition-all">
          Abrir automação <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
      </div>
    <?php endforeach; ?>

    <div class="bg-surface/40 border border-dashed border-bord rounded-xl p-5 flex flex-col items-center justify-center text-center">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="text-tmuted mb-2"><path d="M12 5v14M5 12h14"/></svg>
      <p class="text-tmuted text-xs">Próximas automações aparecem aqui automaticamente</p>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/rodape.php'; ?>
