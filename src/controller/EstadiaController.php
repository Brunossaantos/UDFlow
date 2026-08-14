<?php

namespace Udflow\controller;

use Udflow\rn\ExecucaoRn;

class EstadiaController extends AutomacaoController
{
    protected function chave(): string
    {
        return 'estadia';
    }

    protected function rn(): ExecucaoRn
    {
        return new ExecucaoRn();
    }

    protected function view(): string
    {
        return 'estadia.php';
    }
}
