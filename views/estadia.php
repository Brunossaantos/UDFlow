<?php
/**
 * views/estadia.php
 * Renderizado por EstadiaController::tela()
 */

$paginaAtiva = 'estadia';
$tituloPagina = 'Estadia';
require __DIR__ . '/partials/cabecalho.php';

$chaveRota = 'estadia';
$rotuloBotao = 'Executar agora';
$avisoEmailUdlog = false;
$avisoProximoDisparo = 'Essa automação também roda sozinha, todo mês, no dia e horário configurados no Cronograma.';
$mostrarColunaOrigem = true;

require __DIR__ . '/partials/tela-execucao.php';
require __DIR__ . '/partials/rodape.php';
