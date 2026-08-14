<?php

namespace Udflow\controller;

use Udflow\rn\ClienteRn;
use Udflow\dao\ClienteDao;
use Udflow\dao\UnidadeDao;
use Udflow\dao\AutomacaoDao;
use Udflow\util\Csrf;
use Udflow\util\ControleAcesso;

class AdminClienteController
{
    public function tela(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        $unidadeFiltro = isset($_GET['unidade_id']) ? (int) $_GET['unidade_id'] : null;

        $clienteDao = new ClienteDao();
        $clientes = $clienteDao->listarTodos($unidadeFiltro);

        // monta um array à parte com cliente + config de KPI + automações
        // vinculadas - evita anexar propriedade "na marra" no Model,
        // que o PHP moderno reclama (dynamic property deprecated)
        $clientesComDetalhes = array_map(function ($cliente) use ($clienteDao) {
            return [
                'cliente' => $cliente,
                'kpiConfig' => $clienteDao->buscarKpiConfig($cliente->id),
                'automacoesAtivas' => $clienteDao->automacoesDoCliente($cliente->id),
            ];
        }, $clientes);

        $unidades = (new UnidadeDao())->listarTodas();
        $automacoes = (new AutomacaoDao())->listarTodas();

        require __DIR__ . '/../../views/admin/clientes.php';
    }

    public function criar(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();
        $this->salvar(null);
    }

    public function atualizar(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();
        $id = (int) ($_POST['id'] ?? 0);
        $this->salvar($id ?: null);
    }

    private function salvar(?int $id): void
    {
        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            $this->voltarComErro('Sessão expirada, atualiza a página.');
        }

        $dados = [
            'unidade_id' => $_POST['unidade_id'] ?? null,
            'codigo_cliente' => trim($_POST['codigo_cliente'] ?? ''),
            'razao_social' => trim($_POST['razao_social'] ?? ''),
            'nome_exibicao' => trim($_POST['nome_exibicao'] ?? ''),
            'cnpj' => trim($_POST['cnpj'] ?? ''),
            'email_responsavel' => trim($_POST['email_responsavel'] ?? ''),
            'logo_url' => trim($_POST['logo_url'] ?? ''),
            'cor_primaria' => trim($_POST['cor_primaria'] ?? ''),
            'cor_secundaria' => trim($_POST['cor_secundaria'] ?? ''),
            'ativo' => $_POST['ativo'] ?? '1',
        ];

        // automacoes[] vem como lista de ids marcados no formulário (checkbox)
        $automacoesMarcadas = array_fill_keys(array_map('intval', $_POST['automacoes'] ?? []), true);

        $clienteRn = new ClienteRn();
        $resultado = $id === null
            ? $clienteRn->criar($dados, $automacoesMarcadas, ControleAcesso::usuarioLogadoId())
            : $clienteRn->atualizar($id, $dados, $automacoesMarcadas, ControleAcesso::usuarioLogadoId());

        if (!$resultado['sucesso']) {
            $this->voltarComErro($resultado['mensagem']);
        }

        $_SESSION['flash_sucesso'] = $id === null ? 'Cliente cadastrado.' : 'Cliente atualizado.';
        header('Location: index.php?pagina=admin-clientes');
        exit;
    }

    private function voltarComErro(string $mensagem)
    {
        $_SESSION['flash_erro'] = $mensagem;
        header('Location: index.php?pagina=admin-clientes');
        exit;
    }
}
