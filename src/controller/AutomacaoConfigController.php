<?php

namespace Udflow\controller;

use Udflow\rn\AutomacaoConfigRn;
use Udflow\dao\AutomacaoDao;
use Udflow\util\Saida;
use Udflow\util\Csrf;
use Udflow\util\ControleAcesso;

/**
 * AutomacaoConfigController
 * 
 * Gerencia a interface de configuração customizável de automações.
 * Valida permissões, formata dados e chama a RN.
 */
class AutomacaoConfigController
{
    private AutomacaoConfigRn $rn;
    private AutomacaoDao $automacaoDao;

    public function __construct()
    {
        $this->rn = new AutomacaoConfigRn();
        $this->automacaoDao = new AutomacaoDao();
    }

    // ========================================================================
    // VISUALIZAÇÃO
    // ========================================================================

    /**
     * Tela inicial de configuração de automações
     */
    public function tela(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        $automacoes = $this->automacaoDao->listarTodas();
        $paginaAtiva = 'admin-automacao-config';
        $tituloPagina = 'Administração · Configurar Automações';

        require __DIR__ . '/../../views/admin/automacao-config.php';
    }

    /**
     * Editar configuração de uma automação específica
     */
    public function editar(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        $automacaoId = (int) ($_GET['id'] ?? 0);
        if ($automacaoId <= 0) {
            $this->voltarComErro('ID de automação inválido.');
        }

        $automacao = $this->automacaoDao->buscarPorId($automacaoId);
        if (!$automacao) {
            $this->voltarComErro('Automação não encontrada.');
        }

        $config = $this->rn->buscarConfigCompleta($automacaoId);
        $paginaAtiva = 'admin-automacao-config';
        $tituloPagina = 'Configurar: ' . $automacao['nome'];

        require __DIR__ . '/../../views/admin/automacao-config-form.php';
    }

    // ========================================================================
    // CAMPOS DO PAYLOAD
    // ========================================================================

    /**
     * Criar novo campo de payload
     */
    public function criarCampo(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $automacaoId = (int) ($_POST['automacao_id'] ?? 0);
        $nomeCampo = trim($_POST['nome_campo'] ?? '');
        $tipoDado = $_POST['tipo_dado'] ?? 'string';
        $obrigatorio = isset($_POST['obrigatorio']) && $_POST['obrigatorio'] === '1';
        $valorPadrao = $_POST['valor_padrao'] ?? null;
        $posicao = (int) ($_POST['posicao'] ?? 10);
        $labelCampo = trim($_POST['label_campo'] ?? null);
        $descricao = trim($_POST['descricao'] ?? null);
        $validacaoCustomizada = null;

        // Parsear validação se for JSON
        if (!empty($_POST['validacao_customizada'])) {
            $validacaoCustomizada = json_decode($_POST['validacao_customizada'], true);
        }

        $resultado = $this->rn->criarCampo(
            $automacaoId,
            $nomeCampo,
            $tipoDado,
            $obrigatorio,
            $valorPadrao,
            $posicao,
            $labelCampo,
            $descricao,
            $validacaoCustomizada,
            ControleAcesso::usuarioLogadoId()
        );

        if (!$resultado['sucesso']) {
            Saida::json($resultado, 422);
        }

        Saida::json(['sucesso' => true, 'id' => $resultado['id']]);
    }

    /**
     * Atualizar campo de payload
     */
    public function atualizarCampo(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $campoId = (int) ($_POST['campo_id'] ?? 0);
        $tipoDado = $_POST['tipo_dado'] ?? 'string';
        $obrigatorio = isset($_POST['obrigatorio']) && $_POST['obrigatorio'] === '1';
        $valorPadrao = $_POST['valor_padrao'] ?? null;
        $posicao = (int) ($_POST['posicao'] ?? 10);
        $labelCampo = trim($_POST['label_campo'] ?? null);
        $descricao = trim($_POST['descricao'] ?? null);
        $validacaoCustomizada = null;

        if (!empty($_POST['validacao_customizada'])) {
            $validacaoCustomizada = json_decode($_POST['validacao_customizada'], true);
        }

        $resultado = $this->rn->atualizarCampo(
            $campoId,
            $tipoDado,
            $obrigatorio,
            $valorPadrao,
            $posicao,
            $labelCampo,
            $descricao,
            $validacaoCustomizada,
            ControleAcesso::usuarioLogadoId()
        );

        if (!$resultado['sucesso']) {
            Saida::json($resultado, 422);
        }

        Saida::json(['sucesso' => true]);
    }

    /**
     * Deletar campo de payload
     */
    public function deletarCampo(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $campoId = (int) ($_POST['campo_id'] ?? 0);

        $resultado = $this->rn->deletarCampo($campoId, ControleAcesso::usuarioLogadoId());

        if (!$resultado['sucesso']) {
            Saida::json($resultado, 422);
        }

        Saida::json(['sucesso' => true]);
    }

    // ========================================================================
    // REGRAS DE TRANSFORMAÇÃO
    // ========================================================================

    /**
     * Criar nova regra de transformação
     */
    public function criarRegra(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $automacaoId = (int) ($_POST['automacao_id'] ?? 0);
        $campoId = !empty($_POST['campo_id']) ? (int) $_POST['campo_id'] : null;
        $tipoRegra = $_POST['tipo_regra'] ?? 'fixed_value';
        $ordemExecucao = (int) ($_POST['ordem_execucao'] ?? 10);
        $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

        // Parsear configuração
        $configuracao = [];
        switch ($tipoRegra) {
            case 'fixed_value':
                $configuracao = ['valor' => $_POST['valor'] ?? ''];
                break;
            case 'map_from_banco':
                $configuracao = [
                    'tabela' => $_POST['tabela'] ?? '',
                    'coluna' => $_POST['coluna'] ?? '',
                    'condicao' => $_POST['condicao'] ?? '',
                ];
                break;
            case 'timestamp':
                $configuracao = ['formato' => $_POST['formato'] ?? 'Y-m-d H:i:s'];
                break;
            case 'uuid':
                $configuracao = ['versao' => (int) ($_POST['versao'] ?? 4)];
                break;
            case 'expression':
                $configuracao = ['codigo' => $_POST['codigo'] ?? ''];
                break;
            case 'concatenate':
                $campos = json_decode($_POST['campos'] ?? '[]', true);
                $configuracao = ['campos' => is_array($campos) ? $campos : []];
                break;
            case 'if_condition':
                $configuracao = [
                    'condicao' => $_POST['condicao'] ?? '',
                    'valor_true' => $_POST['valor_true'] ?? '',
                    'valor_false' => $_POST['valor_false'] ?? '',
                ];
                break;
        }

        $resultado = $this->rn->criarRegra(
            $automacaoId,
            $tipoRegra,
            $configuracao,
            $campoId,
            $ordemExecucao,
            $ativo,
            ControleAcesso::usuarioLogadoId()
        );

        if (!$resultado['sucesso']) {
            Saida::json($resultado, 422);
        }

        Saida::json(['sucesso' => true, 'id' => $resultado['id']]);
    }

    /**
     * Atualizar regra de transformação
     */
    public function atualizarRegra(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $regraId = (int) ($_POST['regra_id'] ?? 0);
        $tipoRegra = $_POST['tipo_regra'] ?? 'fixed_value';
        $ordemExecucao = (int) ($_POST['ordem_execucao'] ?? 10);
        $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

        // Parsear configuração conforme tipo
        $configuracao = [];
        switch ($tipoRegra) {
            case 'fixed_value':
                $configuracao = ['valor' => $_POST['valor'] ?? ''];
                break;
            case 'map_from_banco':
                $configuracao = [
                    'tabela' => $_POST['tabela'] ?? '',
                    'coluna' => $_POST['coluna'] ?? '',
                    'condicao' => $_POST['condicao'] ?? '',
                ];
                break;
            case 'timestamp':
                $configuracao = ['formato' => $_POST['formato'] ?? 'Y-m-d H:i:s'];
                break;
            case 'uuid':
                $configuracao = ['versao' => (int) ($_POST['versao'] ?? 4)];
                break;
            case 'expression':
                $configuracao = ['codigo' => $_POST['codigo'] ?? ''];
                break;
            case 'concatenate':
                $campos = json_decode($_POST['campos'] ?? '[]', true);
                $configuracao = ['campos' => is_array($campos) ? $campos : []];
                break;
            case 'if_condition':
                $configuracao = [
                    'condicao' => $_POST['condicao'] ?? '',
                    'valor_true' => $_POST['valor_true'] ?? '',
                    'valor_false' => $_POST['valor_false'] ?? '',
                ];
                break;
        }

        $resultado = $this->rn->atualizarRegra(
            $regraId,
            $tipoRegra,
            $configuracao,
            $ordemExecucao,
            $ativo,
            ControleAcesso::usuarioLogadoId()
        );

        if (!$resultado['sucesso']) {
            Saida::json($resultado, 422);
        }

        Saida::json(['sucesso' => true]);
    }

    /**
     * Deletar regra de transformação
     */
    public function deletarRegra(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $regraId = (int) ($_POST['regra_id'] ?? 0);

        $resultado = $this->rn->deletarRegra($regraId, ControleAcesso::usuarioLogadoId());

        if (!$resultado['sucesso']) {
            Saida::json($resultado, 422);
        }

        Saida::json(['sucesso' => true]);
    }

    // ========================================================================
    // HEADERS CUSTOMIZÁVEIS
    // ========================================================================

    /**
     * Criar novo header customizado
     */
    public function criarHeader(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $automacaoId = (int) ($_POST['automacao_id'] ?? 0);
        $chave = trim($_POST['chave'] ?? '');
        $valor = trim($_POST['valor'] ?? '');
        $posicao = (int) ($_POST['posicao'] ?? 10);
        $valorDinamico = isset($_POST['valor_dinamico']) && $_POST['valor_dinamico'] === '1';
        $regraId = !empty($_POST['regra_id']) ? (int) $_POST['regra_id'] : null;

        $resultado = $this->rn->criarHeader(
            $automacaoId,
            $chave,
            $valor,
            $posicao,
            $valorDinamico,
            $regraId,
            ControleAcesso::usuarioLogadoId()
        );

        if (!$resultado['sucesso']) {
            Saida::json($resultado, 422);
        }

        Saida::json(['sucesso' => true, 'id' => $resultado['id']]);
    }

    /**
     * Atualizar header customizado
     */
    public function atualizarHeader(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $headerId = (int) ($_POST['header_id'] ?? 0);
        $valor = trim($_POST['valor'] ?? '');
        $posicao = (int) ($_POST['posicao'] ?? 10);
        $valorDinamico = isset($_POST['valor_dinamico']) && $_POST['valor_dinamico'] === '1';
        $regraId = !empty($_POST['regra_id']) ? (int) $_POST['regra_id'] : null;

        $resultado = $this->rn->atualizarHeader(
            $headerId,
            $valor,
            $posicao,
            $valorDinamico,
            $regraId,
            ControleAcesso::usuarioLogadoId()
        );

        if (!$resultado['sucesso']) {
            Saida::json($resultado, 422);
        }

        Saida::json(['sucesso' => true]);
    }

    /**
     * Deletar header customizado
     */
    public function deletarHeader(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $headerId = (int) ($_POST['header_id'] ?? 0);

        $resultado = $this->rn->deletarHeader($headerId, ControleAcesso::usuarioLogadoId());

        if (!$resultado['sucesso']) {
            Saida::json($resultado, 422);
        }

        Saida::json(['sucesso' => true]);
    }

    // ========================================================================
    // VISUALIZAR LOGS E ESTATÍSTICAS
    // ========================================================================

    /**
     * Ver logs de execução de uma automação
     */
    public function verLogs(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        $automacaoId = (int) ($_GET['automacao_id'] ?? 0);
        if ($automacaoId <= 0) {
            Saida::json(['sucesso' => false, 'mensagem' => 'ID de automação inválido.'], 422);
        }

        $logs = $this->rn->buscarLogs($automacaoId, 100);
        $estadisticas = $this->rn->obterEstatisticas($automacaoId);

        Saida::json([
            'sucesso' => true,
            'logs' => $logs,
            'estatisticas' => $estadisticas,
        ]);
    }

    /**
     * Ver logs com erros apenas
     */
    public function verLogsComErro(): void
    {
        ControleAcesso::exigirAdminDeAlgumaAutomacao();

        $automacaoId = (int) ($_GET['automacao_id'] ?? 0);
        if ($automacaoId <= 0) {
            Saida::json(['sucesso' => false, 'mensagem' => 'ID de automação inválido.'], 422);
        }

        $logs = $this->rn->buscarLogsComErro($automacaoId, 50);

        Saida::json([
            'sucesso' => true,
            'logs' => $logs,
        ]);
    }

    // ========================================================================
    // AUXILIARES
    // ========================================================================

    /**
     * Voltar com erro
     */
    private function voltarComErro(string $mensagem): void
    {
        $_SESSION['flash_erro'] = $mensagem;
        header('Location: index.php?pagina=admin-automacao-config');
        exit;
    }
}