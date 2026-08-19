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

        $pagina = (int) ($_GET['p'] ?? 1);
        if ($pagina < 1) $pagina = 1;
        $itensPorPagina = 15;
        $offset = ($pagina - 1) * $itensPorPagina;

        $filtros = array_filter([
            'automacao_id' => $_GET['automacao_id'] ?? null,
            'unidade_id' => $_GET['unidade_id'] ?? null,
            'limit' => $itensPorPagina,
            'offset' => $offset,
        ]);

        $filtrosSemPaginacao = array_filter([
            'automacao_id' => $_GET['automacao_id'] ?? null,
            'unidade_id' => $_GET['unidade_id'] ?? null,
        ]);

        $cronogramaRaw = (new CronogramaRn())->listar($filtros);
        $cronograma = $this->agruparCronograma($cronogramaRaw);

        $totalRegistros = (new CronogramaRn())->contar($filtrosSemPaginacao);
        $totalPaginas = (int) ceil($totalRegistros / $itensPorPagina);
        $paginaAtual = $pagina;

        $automacoes = array_filter((new AutomacaoDao())->listarTodas(), fn($a) => $a['possui_agendamento']);
        $unidades = (new UnidadeDao())->listarTodas();
        $clientes = (new ClienteDao())->listarTodos();

        require __DIR__ . '/../../views/admin/cronograma.php';
    }

    private function agruparCronograma(array $registros): array
    {
        $agrupado = [];

        foreach ($registros as $item) {
            // Agrupar por automacao + cliente (não por unidade)
            $chave = $item['automacao_id'] . '-' . $item['cliente_id'];

            if (!isset($agrupado[$chave])) {
                $agrupado[$chave] = [
                    'automacao_id' => $item['automacao_id'],
                    'automacao_nome' => $item['automacao_nome'],
                    'automacao_chave' => $item['automacao_chave'],
                    'cliente_id' => $item['cliente_id'],
                    'cliente_nome' => $item['cliente_nome'],
                    'unidade_id' => $item['unidade_id'] ?? null,
                    'unidade_nome' => $item['unidade_nome'],
                    'horarios' => [],
                ];
            }

            $agrupado[$chave]['horarios'][] = [
                'id' => $item['id'],
                'frequencia' => $item['frequencia'],
                'dia_mes' => $item['dia_mes'],
                'dias_semana' => $item['dias_semana'],
                'horario' => $item['horario'],
                'ativo' => $item['ativo'],
            ];
        }

        return array_values($agrupado);
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

    public function alternarHorarioIndividual(): void
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

    public function deletarHorario(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $id = (int) ($_POST['cronograma_id'] ?? 0);

        if ($id <= 0) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Item inválido.'], 422);
        }

        $resultado = (new CronogramaRn())->deletar($id, ControleAcesso::usuarioLogadoId());

        if (!$resultado['sucesso']) {
            Saida::json($resultado, 422);
        }

        Saida::json(['sucesso' => true]);
    }

    public function atualizarHorario(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $frequencia = $_POST['frequencia'] ?? '';
        $horario = trim($_POST['horario'] ?? '');
        $diaMes = $frequencia === 'mensal' ? (int) ($_POST['dia_mes'] ?? 0) : null;
        $diasMarcados = array_map('intval', $_POST['dias_semana'] ?? []);
        $diasSemana = !empty($diasMarcados) ? implode(',', $diasMarcados) : null;

        if ($id <= 0 || empty($horario) || empty($frequencia)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Dados inválidos.'], 422);
        }

        if (!in_array($frequencia, ['diaria', 'mensal'])) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Frequência inválida.'], 422);
        }

        if ($frequencia === 'mensal' && $diaMes <= 0) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Dia do mês obrigatório para frequência mensal.'], 422);
        }

        $resultado = (new CronogramaRn())->atualizar(
            id: $id,
            frequencia: $frequencia,
            horario: $horario,
            diasSemana: $diasSemana,
            diaMes: $diaMes,
            executorId: ControleAcesso::usuarioLogadoId()
        );

        if (!$resultado['sucesso']) {
            Saida::json($resultado, 422);
        }

        Saida::json(['sucesso' => true]);
    }

    private function voltarComErro(string $mensagem): void
    {
        $_SESSION['flash_erro'] = $mensagem;
        header('Location: index.php?pagina=admin-cronograma');
        exit;
    }
}