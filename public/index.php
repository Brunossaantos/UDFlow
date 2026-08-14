<?php

/**
 * index.php
 *
 * Única porta de entrada do sistema. Toda URL passa por aqui -
 * mesmo sem URL amigável (?pagina=kpi em vez de /automacoes/kpi),
 * isso evita mexer no .htaccess e continua simples de manter numa
 * hospedagem compartilhada como o Hostgator.
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Udflow\util\ControleAcesso;

$rotas = require __DIR__ . '/../config/rotas.php';

$paginaPedida = $_GET['pagina'] ?? (ControleAcesso::estaLogado() ? 'home' : 'login');

if (!array_key_exists($paginaPedida, $rotas)) {
    http_response_code(404);
    echo 'Página não encontrada.';
    exit;
}

$rota = $rotas[$paginaPedida];

// confere se o verbo HTTP bate (rota de POST não pode ser aberta digitando a URL)
$metodoEsperado = $rota['metodo'] ?? 'GET';
if ($_SERVER['REQUEST_METHOD'] !== $metodoEsperado) {
    http_response_code(405);
    echo 'Método não permitido.';
    exit;
}

if ($rota['auth'] ?? false) {
    ControleAcesso::exigirLogin();
}

if (isset($rota['papel'])) {
    ControleAcesso::exigirPapel($rota['papel']['automacao'], $rota['papel']['minimo']);
}

$controller = new $rota['controller']();
$acao = $rota['acao'];
$controller->$acao();
