<?php
/**
 * views/admin/logs.php
 * Renderizado por AdminLogController::tela()
 * Espera $execucoes (de vw_execucoes_detalhadas) e $automacoes
 */

use Udflow\util\Saida;

$paginaAtiva = 'admin-logs';
$tituloPagina = 'Administração · Logs';
require __DIR__ . '/../partials/cabecalho.php';

$statusLabel = [
    'pendente' => ['Pendente', 'bg-amber/10 text-amber'],
    'processando' => ['Processando', 'bg-info/10 text-info'],
    'concluido' => ['Concluído', 'bg-success/10 text-success'],
    'erro' => ['Erro', 'bg-danger/10 text-danger'],
];
?>

<h1 class="font-display font-semibold text-xl mb-1">Logs e status</h1>
<p class="text-tsecondary text-sm mb-6">Visão geral de todas as solicitações, de todos os usuários e automações.</p>

<form method="GET" action="index.php" class="flex flex-wrap gap-3 mb-5">
  <input type="hidden" name="pagina" value="admin-logs">
  <select name="automacao" onchange="this.form.submit()" class="bg-surface border border-bord rounded-lg px-3 py-2 text-xs glow-focus">
    <option value="">Todas as automações</option>
    <?php foreach ($automacoes as $automacao): ?>
      <option value="<?= Saida::e($automacao['chave']) ?>" <?= ($_GET['automacao'] ?? '') === $automacao['chave'] ? 'selected' : '' ?>><?= Saida::e($automacao['nome']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status" onchange="this.form.submit()" class="bg-surface border border-bord rounded-lg px-3 py-2 text-xs glow-focus">
    <option value="">Todos os status</option>
    <?php foreach (['pendente', 'processando', 'concluido', 'erro'] as $s): ?>
      <option value="<?= $s ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= $statusLabel[$s][0] ?></option>
    <?php endforeach; ?>
  </select>
  <input type="date" name="data" value="<?= Saida::e($_GET['data'] ?? '') ?>" onchange="this.form.submit()" class="bg-surface border border-bord rounded-lg px-3 py-2 text-xs glow-focus ml-auto">
</form>

<div class="bg-surface border border-bord rounded-2xl overflow-hidden">
  <table class="w-full text-sm">
    <thead>
      <tr class="border-b border-bord text-left text-tmuted text-xs">
        <th class="font-medium px-5 py-3">Automação</th>
        <th class="font-medium px-5 py-3">Cliente</th>
        <th class="font-medium px-5 py-3">Usuário</th>
        <th class="font-medium px-5 py-3">E-mail</th>
        <th class="font-medium px-5 py-3">Origem</th>
        <th class="font-medium px-5 py-3">Status</th>
        <th class="font-medium px-5 py-3">Data</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($execucoes)): ?>
        <tr><td colspan="7" class="px-5 py-10 text-center text-tmuted text-sm">Nenhuma execução encontrada com esses filtros.</td></tr>
      <?php else: ?>
        <?php foreach ($execucoes as $e): ?>
          <?php [$rotuloStatus, $corStatus] = $statusLabel[$e['status']] ?? ['—', 'bg-tmuted/10 text-tmuted']; ?>
          <tr class="border-b border-bordsoft last:border-0 hover:bg-elevated/40 transition">
            <td class="px-5 py-3.5 text-xs"><?= Saida::e($e['automacao_nome']) ?></td>
            <td class="px-5 py-3.5 font-medium"><?= Saida::e($e['cliente_nome']) ?></td>
            <td class="px-5 py-3.5 text-tsecondary text-xs"><?= Saida::e($e['usuario_nome'] ?? 'Sistema (automático)') ?></td>
            <td class="px-5 py-3.5 text-tsecondary font-mono text-xs"><?= Saida::e($e['email_destino']) ?></td>
            <td class="px-5 py-3.5 text-xs text-tsecondary"><?= $e['origem'] === 'automatico' ? 'Automático' : 'Manual' ?></td>
            <td class="px-5 py-3.5"><span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full <?= $corStatus ?>"><?= Saida::e($rotuloStatus) ?></span></td>
            <td class="px-5 py-3.5 text-tmuted text-xs font-mono"><?= Saida::e(date('d/m H:i', strtotime($e['criado_em']))) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../partials/rodape.php'; ?>
