<?php
/**
 * views/admin/cronograma.php
 * Renderizado por AdminCronogramaController::tela()
 * Espera $cronograma, $automacoes (só as que possuem agendamento), $unidades
 */

use Udflow\util\Saida;

$paginaAtiva = 'admin-cronograma';
$tituloPagina = 'Administração · Cronograma';
require __DIR__ . '/../partials/cabecalho.php';
?>

<h1 class="font-display font-semibold text-xl mb-1">Cronograma automático</h1>
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
        <tr><td colspan="7" class="px-5 py-10 text-center text-tmuted text-sm">Nenhum item de cronograma cadastrado ainda.</td></tr>
      <?php else: ?>
        <?php foreach ($cronograma as $item): ?>
          <tr class="border-b border-bordsoft last:border-0 hover:bg-elevated/40 transition">
            <td class="px-5 py-3.5 text-xs"><?= Saida::e($item['automacao_nome']) ?></td>
            <td class="px-5 py-3.5 font-medium"><?= Saida::e($item['cliente_nome']) ?></td>
            <td class="px-5 py-3.5 text-tsecondary text-xs"><?= Saida::e($item['unidade_nome']) ?></td>
            <td class="px-5 py-3.5 font-mono text-xs">Dia <?= (int) $item['dia_mes'] ?></td>
            <td class="px-5 py-3.5 font-mono text-xs"><?= Saida::e(substr($item['horario'], 0, 5)) ?></td>
            <td class="px-5 py-3.5">
              <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" onchange="alternarAtivoCronograma(<?= (int) $item['id'] ?>, this.checked)" <?= $item['ativo'] ? 'checked' : '' ?> class="rounded border-bord">
                <span class="text-xs <?= $item['ativo'] ? 'text-success' : 'text-tmuted' ?>"><?= $item['ativo'] ? 'Ativo' : 'Pausado' ?></span>
              </label>
            </td>
            <td class="px-5 py-3.5 text-right">
              <button onclick="executarAgoraCronograma(<?= (int) $item['id'] ?>)" class="text-xs text-flow hover:underline">Executar agora</button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
  async function alternarAtivoCronograma(id, ativo) {
    const resultado = await enviarComCsrf('index.php?pagina=admin-cronograma-ativo', { cronograma_id: id, ativo: ativo ? '1' : '0' });
    if (resultado.sucesso) {
      toast(ativo ? 'Agendamento ativado' : 'Agendamento pausado');
    } else {
      toast(resultado.mensagem || 'Não foi possível atualizar.', 'erro');
    }
  }

  async function executarAgoraCronograma(id) {
    const resultado = await enviarComCsrf('index.php?pagina=admin-cronograma-executar', { cronograma_id: id });
    if (resultado.sucesso) {
      toast('Execução manual disparada');
    } else {
      toast(resultado.mensagem || 'Não foi possível executar.', 'erro');
    }
  }
</script>

<?php require __DIR__ . '/../partials/rodape.php'; ?>
