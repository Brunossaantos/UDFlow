<?php
/**
 * views/admin/logs-sistema.php
 * Renderizado por AdminLogSistemaController::tela()
 * Espera $logs (de tb_logs_sistema) e $contagemPorNivel
 */

use Udflow\util\Saida;

$paginaAtiva = 'admin-logs-sistema';
$tituloPagina = 'Administração · Logs do sistema';
require __DIR__ . '/../partials/cabecalho.php';

$nivelLabel = [
    'warning' => ['Aviso', 'bg-amber/10 text-amber'],
    'error' => ['Erro', 'bg-danger/10 text-danger'],
    'exception' => ['Exceção', 'bg-danger/10 text-danger'],
    'fatal' => ['Fatal', 'bg-danger/20 text-danger'],
];
?>

<h1 class="font-display font-semibold text-xl mb-1">Logs do sistema</h1>
<p class="text-tsecondary text-sm mb-6">Erros, exceções e falhas capturados automaticamente em todo o sistema. Visível só pra super administradores.</p>

<div class="flex flex-wrap gap-3 mb-5">
  <?php foreach ($contagemPorNivel as $nivel => $total): ?>
    <?php [$rotulo, $cor] = $nivelLabel[$nivel] ?? [$nivel, 'bg-tmuted/10 text-tmuted']; ?>
    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full <?= $cor ?>"><?= Saida::e($rotulo) ?>: <?= (int) $total ?></span>
  <?php endforeach; ?>
</div>

<form method="GET" action="index.php" class="flex flex-wrap gap-3 mb-5">
  <input type="hidden" name="pagina" value="admin-logs-sistema">
  <select name="nivel" onchange="this.form.submit()" class="bg-surface border border-bord rounded-lg px-3 py-2 text-xs glow-focus">
    <option value="">Todos os níveis</option>
    <?php foreach (['warning', 'error', 'exception', 'fatal'] as $n): ?>
      <option value="<?= $n ?>" <?= ($_GET['nivel'] ?? '') === $n ? 'selected' : '' ?>><?= $nivelLabel[$n][0] ?></option>
    <?php endforeach; ?>
  </select>
  <input type="date" name="data" value="<?= Saida::e($_GET['data'] ?? '') ?>" onchange="this.form.submit()" class="bg-surface border border-bord rounded-lg px-3 py-2 text-xs glow-focus ml-auto">
</form>

<div class="bg-surface border border-bord rounded-2xl overflow-hidden">
  <table class="w-full text-sm">
    <thead>
      <tr class="border-b border-bord text-left text-tmuted text-xs">
        <th class="font-medium px-5 py-3">Nível</th>
        <th class="font-medium px-5 py-3">Mensagem</th>
        <th class="font-medium px-5 py-3">Local</th>
        <th class="font-medium px-5 py-3">Data</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($logs)): ?>
        <tr><td colspan="4" class="px-5 py-10 text-center text-tmuted text-sm">Nenhum log encontrado com esses filtros.</td></tr>
      <?php else: ?>
        <?php foreach ($logs as $log): ?>
          <?php [$rotulo, $cor] = $nivelLabel[$log['nivel']] ?? [$log['nivel'], 'bg-tmuted/10 text-tmuted']; ?>
          <tr class="border-b border-bordsoft last:border-0 hover:bg-elevated/40 transition align-top">
            <td class="px-5 py-3.5"><span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full <?= $cor ?>"><?= Saida::e($rotulo) ?></span></td>
            <td class="px-5 py-3.5 max-w-xl">
              <p class="text-xs"><?= Saida::e($log['mensagem']) ?></p>
              <?php if (!empty($log['contexto'])): ?>
                <details class="mt-1.5">
                  <summary class="text-[11px] text-tmuted cursor-pointer hover:text-tsecondary">detalhes</summary>
                  <pre class="mt-1.5 text-[11px] text-tsecondary bg-elevated/60 rounded-lg p-2.5 overflow-x-auto whitespace-pre-wrap"><?= Saida::e($log['contexto']) ?></pre>
                </details>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3.5 text-tsecondary font-mono text-xs"><?= $log['arquivo'] ? Saida::e(basename($log['arquivo']) . ':' . $log['linha']) : '—' ?></td>
            <td class="px-5 py-3.5 text-tmuted text-xs font-mono whitespace-nowrap"><?= Saida::e(date('d/m H:i', strtotime($log['criado_em']))) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../partials/rodape.php'; ?>
