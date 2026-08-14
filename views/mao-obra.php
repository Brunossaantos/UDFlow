<?php
/**
 * views/mao-obra.php
 * Renderizado por MaoObraController::tela()
 */

$paginaAtiva = 'mao_obra_batida';
$tituloPagina = 'Mão de Obra Batida';
require __DIR__ . '/partials/cabecalho.php';

$chaveRota = 'mao-obra';
$rotuloBotao = 'Executar agora';
$avisoEmailUdlog = false;
$avisoProximoDisparo = 'Essa automação também roda sozinha, todo mês, no dia e horário configurados no Cronograma.';
$mostrarColunaOrigem = true;

require __DIR__ . '/partials/tela-execucao.php';
require __DIR__ . '/partials/rodape.php';
