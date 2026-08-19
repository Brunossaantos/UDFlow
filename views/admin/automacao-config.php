<?php

/**
 * views/admin/automacao-config.php
 * Renderizado por AutomacaoConfigController::tela()
 * Espera $automacoes
 */

use Udflow\util\Saida;

$paginaAtiva = 'admin-automacao-config';
$tituloPagina = 'Administração · Configurar Automações';
require __DIR__ . '/../partials/cabecalho.php';
?>

<div class="flex items-center justify-between mb-1">
    <h1 class="font-display font-semibold text-xl">Configurar Automações</h1>
    <a href="index.php?pagina=admin-automacoes" class="text-xs text-flow hover:underline">
        ← Voltar pra Automações
    </a>
</div>
<p class="text-tsecondary text-sm mb-6">Customize webhooks, campos de payload, transformações e headers para cada automação.</p>

<div class="bg-surface border border-bord rounded-2xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-bord text-left text-tmuted text-xs">
                <th class="font-medium px-5 py-3">Automação</th>
                <th class="font-medium px-5 py-3">Webhook</th>
                <th class="font-medium px-5 py-3">Campos</th>
                <th class="font-medium px-5 py-3">Regras</th>
                <th class="font-medium px-5 py-3">Headers</th>
                <th class="font-medium px-5 py-3 text-right">Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($automacoes)): ?>
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-tmuted text-sm">Nenhuma automação cadastrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($automacoes as $automacao): ?>
                    <tr class="border-b border-bordsoft last:border-0 hover:bg-elevated/40 transition">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <?php if (!empty($automacao['icon_svg'])): ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-flow">
                                        <?= $automacao['icon_svg'] ?>
                                    </svg>
                                <?php endif; ?>
                                <span class="font-medium"><?= Saida::e($automacao['nome']) ?></span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5">
                            <?php if (!empty($automacao['webhook_url'])): ?>
                                <span class="text-[10px] text-tmuted font-mono truncate inline-block max-w-[200px]" title="<?= Saida::e($automacao['webhook_url']) ?>">
                                    <?= Saida::e(substr($automacao['webhook_url'], 0, 30)) ?>...
                                </span>
                            <?php else: ?>
                                <span class="text-tmuted text-xs italic">Não configurado</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-block px-2 py-1 bg-flow/10 text-flow text-xs rounded font-semibold">
                                <?= count($automacao['campos'] ?? []) ?>
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-block px-2 py-1 bg-blue-500/10 text-blue-500 text-xs rounded font-semibold">
                                <?= count($automacao['regras'] ?? []) ?>
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-block px-2 py-1 bg-purple-500/10 text-purple-500 text-xs rounded font-semibold">
                                <?= count($automacao['headers'] ?? []) ?>
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="index.php?pagina=admin-automacao-config-editar&id=<?= (int) $automacao['id'] ?>"
                                class="text-xs text-flow hover:underline font-medium">
                                Configurar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-6 bg-blue-500/5 border border-blue-500/20 rounded-lg p-4">
    <p class="text-xs text-blue-600 mb-2 font-semibold">💡 Como funciona:</p>
    <ul class="text-xs text-blue-600 space-y-1 ml-4">
        <li>• <strong>Campos:</strong> Defina quais dados vão no payload JSON</li>
        <li>• <strong>Regras:</strong> Transforme dados (busque do banco, gere UUID, timestamps, etc)</li>
        <li>• <strong>Headers:</strong> Customize headers HTTP (Authorization, X-Custom-Id, etc)</li>
        <li>• Todos os valores podem ser dinâmicos ou fixos</li>
    </ul>
</div>

<?php require __DIR__ . '/../partials/rodape.php'; ?>