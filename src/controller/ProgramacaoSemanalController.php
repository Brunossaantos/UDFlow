<?php

namespace Udflow\controller;

use Udflow\rn\ExecucaoRn;

class ProgramacaoSemanalController extends AutomacaoController
{
    protected function chave(): string
    {
        return 'programacao_semanal';
    }

    protected function rn(): ExecucaoRn
    {
        return new ExecucaoRn();
    }

    protected function view(): string
    {
        return 'programacao-semanal.php';
    }
}