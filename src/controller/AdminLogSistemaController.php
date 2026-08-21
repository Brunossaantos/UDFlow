<?php

namespace Udflow\controller;

use Udflow\dao\LogSistemaDao;
use Udflow\util\ControleAcesso;

/**
 * AdminLogSistemaController
 *
 * Tela de logs de sistema (erros/exceptions/fatais capturados
 * automaticamente). Exclusiva do super_admin - diferente da tela
 * "admin-logs" (status de execução de automações), que é sobre
 * negócio, não sobre bug do sistema.
 */
class AdminLogSistemaController
{
    public function tela(): void
    {
        ControleAcesso::exigirSuperAdmin();

        $filtros = array_filter([
            'nivel' => $_GET['nivel'] ?? null,
            'data_inicio' => $_GET['data'] ?? null,
        ]);

        $dao = new LogSistemaDao();
        $logs = $dao->listarComFiltros($filtros, 200);
        $contagemPorNivel = $dao->contarPorNivel();

        require __DIR__ . '/../../views/admin/logs-sistema.php';
    }
}
