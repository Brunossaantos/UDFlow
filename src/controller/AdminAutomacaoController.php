<?php

namespace Udflow\controller;

use Udflow\rn\AutomacaoRn;
use Udflow\util\Csrf;
use Udflow\util\Saida;
use Udflow\util\ControleAcesso;

class AdminAutomacaoController
{
    public function tela(): void
    {
        ControleAcesso::exigirSuperAdmin();

        $automacoes = (new AutomacaoRn())->listar();

        require __DIR__ . '/../../views/admin/automacoes.php';
    }

    public function alternarVisibilidade(): void
    {
        ControleAcesso::exigirSuperAdmin();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $id = (int) ($_POST['automacao_id'] ?? 0);
        $visivel = ($_POST['visivel'] ?? '') === '1';

        if ($id <= 0) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Automação inválida.'], 422);
        }

        (new AutomacaoRn())->alternarVisibilidade($id, $visivel, ControleAcesso::usuarioLogadoId());

        Saida::json(['sucesso' => true]);
    }
}
