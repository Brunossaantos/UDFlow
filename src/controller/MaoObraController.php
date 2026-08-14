<?php

namespace Udflow\controller;

use Udflow\rn\ExecucaoRn;

class MaoObraController extends AutomacaoController
{
    protected function chave(): string
    {
        return 'mao_obra_batida';
    }

    protected function rn(): ExecucaoRn
    {
        return new ExecucaoRn();
    }

    protected function view(): string
    {
        return 'mao-obra.php';
    }
}
