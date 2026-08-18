<?php

namespace Udflow\controller;

use Udflow\rn\CronogramaRn;
use Udflow\dao\AutomacaoDao;
use Udflow\dao\UnidadeDao;
use Udflow\dao\ClienteDao;
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
        $automacoes = array_filter((new AutomacaoDao())->listarTodas(), fn($a) => $a['possui_agendamento']);
        $unidades = (new UnidadeDao())->listarTodas();
        $clientes = (new ClienteDao())->listarTodos();

        require __DIR__ . '/../../views/admin/cronograma.php';
    }

    public function criar(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            $this->voltarComErro('Sessão expirada, atualiza a página.');
        }

        $automacaoId = (int) ($_POST['automacao_id'] ?? 0);
        $clienteId = (int) ($_POST['cliente_id'] ?? 0);
        $frequencia = $_POST['frequencia'] ?? '';
        $diaMes = $frequencia === 'mensal' ? (int) ($_POST['dia_mes'] ?? 0) : null;
        $horario = trim($_POST['horario'] ?? '');

        $diasMarcados = array_map('intval', $_POST['dias_semana'] ?? []);
        $diasSemana = !empty($diasMarcados) ? implode(',', $diasMarcados) : null;

        if ($automacaoId <= 0 || $clienteId <= 0) {
            $this->voltarComErro('Escolhe a automação e o cliente.');
        }

        $resultado = (new CronogramaRn())->criar(
            $automacaoId,
            $clienteId,
            $frequencia,
            $diasSemana,
            $diaMes,
            $horario,
            ControleAcesso::usuarioLogadoId()
        );

        if (!$resultado['sucesso']) {
            $this->voltarComErro($resultado['mensagem']);
        }

        $_SESSION['flash_sucesso'] = 'Agendamento criado.';
        header('Location: index.php?pagina=admin-cronograma');
        exit;
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

    private function voltarComErro(string $mensagem): void
    {
        $_SESSION['flash_erro'] = $mensagem;
        header('Location: index.php?pagina=admin-cronograma');
        exit;
    }
}
