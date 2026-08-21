<?php

/**
 * views/automacao.php
 * 
 * View GENÉRICA para TODAS as automações!
 * Renderizado por AutomacaoController::tela()
 * 
 * Carrega tudo do banco (tb_automacoes) - sem hardcoding!
 * 
 * Espera:
 * - $this->chave (string) - chave da automação
 * - $this->automacao (array) - dados completos da automação do banco
 * - $minhasExecucoes (array) - execuções do usuário
 */

$paginaAtiva = $_GET['pagina'] ?? 'automacao';
$tituloPagina = $this->automacao['nome'] ?? 'Automação';

require __DIR__ . '/partials/cabecalho.php';

// Dados que vêm do banco (tb_automacoes)
// IMPORTANTE: usa a rota da URL (?pagina=programacao-semanal), não a
// chave do banco (programacao_semanal) - o JS monta "{chaveRota}-clientes"
// e essa rota é definida em config/rotas.php com hífen, não underscore.
$chaveRota = $_GET['pagina'] ?? $this->chave;
$rotuloBotao = $this->automacao['label_botao'] ?? 'Executar agora';
$avisoEmailUdlog = $this->automacao['aviso_email_udlog'] ?? false;
$avisoProximoDisparo = $this->automacao['aviso_proximo_disparo'] 
    ?? 'Essa automação também roda sozinha, no horário configurado no Cronograma.';
$mostrarColunaOrigem = $this->automacao['mostrar_coluna_origem'] ?? false;

require __DIR__ . '/partials/tela-execucao.php';
require __DIR__ . '/partials/rodape.php';