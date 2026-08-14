<?php
/**
 * views/kpi.php
 * Renderizado por KpiController::tela() (via AutomacaoController::tela())
 * Espera $minhasExecucoes (vindo do Controller)
 */

$paginaAtiva = 'kpi';
$tituloPagina = 'KPI · Relatórios Anuais';
require __DIR__ . '/partials/cabecalho.php';

$chaveRota = 'kpi';
$rotuloBotao = 'Gerar e enviar';
$avisoEmailUdlog = true;
$avisoProximoDisparo = null; // KPI não tem mais agendamento automático
$mostrarColunaOrigem = false;

require __DIR__ . '/partials/tela-execucao.php';
require __DIR__ . '/partials/rodape.php';
