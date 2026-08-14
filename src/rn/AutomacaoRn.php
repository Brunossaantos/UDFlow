<?php

namespace Udflow\rn;

use Udflow\dao\AutomacaoDao;
use Udflow\dao\LogAdminDao;

class AutomacaoRn
{
    private AutomacaoDao $automacaoDao;
    private LogAdminDao $logAdminDao;

    public function __construct()
    {
        $this->automacaoDao = new AutomacaoDao();
        $this->logAdminDao = new LogAdminDao();
    }

    public function listar(): array
    {
        return $this->automacaoDao->listarTodas();
    }

    public function alternarVisibilidade(int $id, bool $visivel, int $executorId): void
    {
        $this->automacaoDao->atualizarVisibilidade($id, $visivel);
        $acao = $visivel ? 'automacao.liberada_para_usuarios' : 'automacao.restrita_a_admins';
        $this->logAdminDao->registrar($executorId, $acao, 'tb_automacoes', $id, null);
    }
}
