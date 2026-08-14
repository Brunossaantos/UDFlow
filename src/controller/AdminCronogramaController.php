<?php

namespace Udflow\controller;

use Udflow\rn\CronogramaRn;
use Udflow\dao\AutomacaoDao;
use Udflow\dao\UnidadeDao;
use Udflow\util\Csrf;
use Udflow\util\Saida;
use Udflow\util\ControleAcesso;

class AdminCronogramaController
{
    public function tela(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        $filtros = array_filter([
            'automacao_id' => $_GET['automacao_id'] ?? null,
            'unidade_id' => $_GET['unidade_id'] ?? null,
        ]);

        $cronograma = (new CronogramaRn())->listar($filtros);
        $automacoes = array_filter((new AutomacaoDao())->listarTodas(), fn ($a) => $a['possui_agendamento']);
        $unidades = (new UnidadeDao())->listarTodas();

        require __DIR__ . '/../../views/admin/cronograma.php';
    }

    public function alternarAtivo(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $id = (int) ($_POST['cronograma_id'] ?? 0);
        $ativo = ($_POST['ativo'] ?? '') === '1';

        if ($id <= 0) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Item inválido.'], 422);
        }

        (new CronogramaRn())->alternarAtivo($id, $ativo, ControleAcesso::usuarioLogadoId());

        Saida::json(['sucesso' => true]);
    }

    public function executarAgora(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $id = (int) ($_POST['cronograma_id'] ?? 0);
        if ($id <= 0) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Item inválido.'], 422);
        }

        $resultado = (new CronogramaRn())->executarAgora($id, ControleAcesso::usuarioLogadoId());

        Saida::json($resultado, $resultado['sucesso'] ? 200 : 422);
    }
}
