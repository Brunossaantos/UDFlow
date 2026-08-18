<?php
$paginaAtiva = 'programacao_semanal';
$tituloPagina = 'Programação Semanal';
require __DIR__ . '/partials/cabecalho.php';

$chaveRota = 'programacao-semanal';
$rotuloBotao = 'Executar agora';
$avisoEmailUdlog = false;
$avisoProximoDisparo = 'Essa automação também roda sozinha, conforme configurado no Cronograma.';
$mostrarColunaOrigem = true;

require __DIR__ . '/partials/tela-execucao.php';
require __DIR__ . '/partials/rodape.php';