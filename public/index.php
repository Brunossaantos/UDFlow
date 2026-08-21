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
use Udflow\dao\AutomacaoDao;
use Udflow\controller\AutomacaoController;

/**
 * Automações cadastradas pela tela de Automações (super_admin) não
 * ganham uma entrada fixa em rotas.php - a rota é resolvida na hora,
 * contra tb_automacoes, sempre pro mesmo AutomacaoController e pras
 * mesmas 4 ações. Mantém a mesma garantia de segurança do whitelist
 * estático (não dá pra montar caminho de arquivo arbitrário): só
 * dispacha se a rota bater com uma automação ativa cadastrada de
 * verdade no banco.
 */
function resolverRotaDeAutomacao(string $pagina): ?array
{
    $sufixos = [
        '-clientes' => ['acao' => 'buscarClientes', 'metodo' => 'GET'],
        '-status' => ['acao' => 'statusExecucoes', 'metodo' => 'GET'],
        '-enviar' => ['acao' => 'enviar', 'metodo' => 'POST'],
    ];

    $rotaBase = $pagina;
    $acao = 'tela';
    $metodo = 'GET';

    foreach ($sufixos as $sufixo => $info) {
        if (str_ends_with($pagina, $sufixo)) {
            $rotaBase = substr($pagina, 0, -strlen($sufixo));
            $acao = $info['acao'];
            $metodo = $info['metodo'];
            break;
        }
    }

    $automacao = (new AutomacaoDao())->buscarPorRota($rotaBase);
    if ($automacao === null) {
        return null;
    }

    return [
        'auth' => true,
        'metodo' => $metodo,
        'papel' => ['automacao' => $automacao['chave'], 'minimo' => 'usuario'],
        'controller' => AutomacaoController::class,
        'acao' => $acao,
    ];
}

$rotas = require __DIR__ . '/../config/rotas.php';

$paginaPedida = $_GET['pagina'] ?? (ControleAcesso::estaLogado() ? 'home' : 'login');

$rota = $rotas[$paginaPedida] ?? resolverRotaDeAutomacao($paginaPedida);

if ($rota === null) {
    http_response_code(404);
    echo 'Página não encontrada.';
    exit;
}

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
