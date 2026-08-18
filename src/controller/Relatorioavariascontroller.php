<?php

namespace Udflow\controller;

use Udflow\rn\ExecucaoRn;

class RelatorioAvariasController extends AutomacaoController
{
    protected function chave(): string
    {
        return 'relatorio_avarias';
    }

    protected function rn(): ExecucaoRn
    {
        return new ExecucaoRn();
    }

    protected function view(): string
    {
        return 'relatorio-avarias.php';
    }
}