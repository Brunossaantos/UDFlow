<?php

namespace Udflow\controller;

use Udflow\dao\ExecucaoDao;
use Udflow\dao\AutomacaoDao;
use Udflow\util\ControleAcesso;

class AdminLogController
{
    public function tela(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        // filtros vêm da querystring (GET) porque essa tela é só
        // consulta - não faz sentido exigir POST pra filtrar uma lista
        $filtros = [
            'automacao_chave' => $_GET['automacao'] ?? null,
            'status' => $_GET['status'] ?? null,
            'data_inicio' => $_GET['data'] ?? null,
        ];

        $execucoes = (new ExecucaoDao())->listarComFiltros(array_filter($filtros));
        $automacoes = (new AutomacaoDao())->listarTodas();

        require __DIR__ . '/../../views/admin/logs.php';
    }
}
