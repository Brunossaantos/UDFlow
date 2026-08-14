<?php

namespace Udflow\controller;

use Udflow\rn\ExecucaoRn;
use Udflow\rn\KpiExecucaoRn;

class KpiController extends AutomacaoController
{
    protected function chave(): string
    {
        return 'kpi';
    }

    protected function rn(): ExecucaoRn
    {
        return new KpiExecucaoRn();
    }

    protected function view(): string
    {
        return 'kpi.php';
    }
}
