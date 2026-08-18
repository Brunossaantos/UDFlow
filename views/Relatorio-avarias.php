<?php
/**
 * views/relatorio-avarias.php
 * Renderizado por RelatorioAvariasController::tela() (via AutomacaoController::tela())
 * Espera $minhasExecucoes (vindo do Controller)
 */

$paginaAtiva = 'relatorio_avarias';
$tituloPagina = 'Relatório de Avarias';
require __DIR__ . '/partials/cabecalho.php';

$chaveRota = 'relatorio-avarias';
$rotuloBotao = 'Executar agora';
$avisoEmailUdlog = false;
$avisoProximoDisparo = 'Essa automação também roda sozinha, conforme configurado no Cronograma.';
$mostrarColunaOrigem = true;

require __DIR__ . '/partials/tela-execucao.php';
require __DIR__ . '/partials/rodape.php';