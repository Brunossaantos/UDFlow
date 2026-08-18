<?php

/**
 * views/kpi.php
 * Renderizado por KpiController::tela() (via AutomacaoController::tela())
 * Espera $minhasExecucoes (vindo do Controller)
 */

$paginaAtiva = 'kpi';
$tituloPagina = 'KPI';
require __DIR__ . '/partials/cabecalho.php';

$chaveRota = 'kpi';
$rotuloBotao = 'Gerar e enviar';
$avisoEmailUdlog = true;
$avisoProximoDisparo = 'Essa automação também roda sozinha, todo mês, no dia e horário configurados no Cronograma.';
$mostrarColunaOrigem = false;

require __DIR__ . '/partials/tela-execucao.php';
require __DIR__ . '/partials/rodape.php';
