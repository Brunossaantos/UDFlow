<?php

namespace Udflow\rn;

use Udflow\dao\AutomacaoConfigDao;
use Udflow\dao\LogAdminDao;

/**
 * AutomacaoConfigRn
 * 
 * Camada de negócio para gerenciar configurações customizáveis de automações.
 * Valida dados, aplica regras de negócio e orquestra operações no DAO.
 */
class AutomacaoConfigRn
{
    private AutomacaoConfigDao $dao;
    private LogAdminDao $logAdminDao;

    public function __construct()
    {
        $this->dao = new AutomacaoConfigDao();
        $this->logAdminDao = new LogAdminDao();
    }

    // ========================================================================
    // CAMPOS DO PAYLOAD - OPERAÇÕES
    // ========================================================================

    /**
     * Buscar todos os campos de uma automação
     */
    public function buscarCampos(int $automacaoId): array
    {
        return $this->dao->buscarCampos($automacaoId);
    }

    /**
     * Buscar um campo específico
     */
    public function buscarCampo(int $campoId): ?array
    {
        return $this->dao->buscarCampo($campoId);
    }

    /**
     * Criar novo campo com validações
     * 
     * @return array{sucesso: bool, mensagem: ?string, id: ?int}
     */
    public function criarCampo(
        int $automacaoId,
        string $nomeCampo,
        string $tipoDado,
        bool $obrigatorio = false,
        ?string $valorPadrao = null,
        int $posicao = 10,
        ?string $labelCampo = null,
        ?string $descricao = null,
        ?array $validacaoCustomizada = null,
        int $usuarioId = 0
    ): array {
        // Validar nome do campo
        if (empty(trim($nomeCampo))) {
            return ['sucesso' => false, 'mensagem' => 'Nome do campo não pode estar vazio.', 'id' => null];
        }

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $nomeCampo)) {
            return ['sucesso' => false, 'mensagem' => 'Nome do campo deve começar com letra ou underscore e conter apenas letras, números e underscores.', 'id' => null];
        }

        // Validar tipo de dado
        $tiposValidos = ['string', 'integer', 'decimal', 'boolean', 'email', 'timestamp', 'uuid', 'json', 'array', 'date', 'time'];
        if (!in_array($tipoDado, $tiposValidos, true)) {
            return ['sucesso' => false, 'mensagem' => "Tipo de dado '{$tipoDado}' não é válido.", 'id' => null];
        }

        // Validar valor padrão baseado no tipo
        if ($valorPadrao !== null) {
            $validacaoValorPadrao = $this->validarValorPorTipo($valorPadrao, $tipoDado);
            if (!$validacaoValorPadrao['valido']) {
                return ['sucesso' => false, 'mensagem' => $validacaoValorPadrao['mensagem'], 'id' => null];
            }
        }

        // Criar campo
        try {
            $id = $this->dao->criarCampo(
                $automacaoId,
                $nomeCampo,
                $tipoDado,
                $obrigatorio,
                $valorPadrao,
                $posicao,
                $labelCampo,
                $descricao,
                $validacaoCustomizada
            );

            // Registrar log
            $this->logAdminDao->registrar($usuarioId, 'automacao_config.campo_criado', 'tb_automacao_payload_campos', $id, null);

            return ['sucesso' => true, 'mensagem' => null, 'id' => $id];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => 'Erro ao criar campo: ' . $e->getMessage(), 'id' => null];
        }
    }

    /**
     * Atualizar campo de payload
     */
    public function atualizarCampo(
        int $campoId,
        string $tipoDado,
        bool $obrigatorio = false,
        ?string $valorPadrao = null,
        int $posicao = 10,
        ?string $labelCampo = null,
        ?string $descricao = null,
        ?array $validacaoCustomizada = null,
        int $usuarioId = 0
    ): array {
        // Validar tipo de dado
        $tiposValidos = ['string', 'integer', 'decimal', 'boolean', 'email', 'timestamp', 'uuid', 'json', 'array', 'date', 'time'];
        if (!in_array($tipoDado, $tiposValidos, true)) {
            return ['sucesso' => false, 'mensagem' => "Tipo de dado '{$tipoDado}' não é válido."];
        }

        // Buscar campo anterior pra auditoria
        $campoAnterior = $this->dao->buscarCampo($campoId);
        if (!$campoAnterior) {
            return ['sucesso' => false, 'mensagem' => 'Campo não encontrado.'];
        }

        try {
            $this->dao->atualizarCampo(
                $campoId,
                $tipoDado,
                $obrigatorio,
                $valorPadrao,
                $posicao,
                $labelCampo,
                $descricao,
                $validacaoCustomizada
            );

            // Registrar log
            $this->logAdminDao->registrar($usuarioId, 'automacao_config.campo_atualizado', 'tb_automacao_payload_campos', $campoId, null);

            return ['sucesso' => true, 'mensagem' => null];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => 'Erro ao atualizar campo: ' . $e->getMessage()];
        }
    }

    /**
     * Deletar campo de payload
     */
    public function deletarCampo(int $campoId, int $usuarioId = 0): array
    {
        $campo = $this->dao->buscarCampo($campoId);
        if (!$campo) {
            return ['sucesso' => false, 'mensagem' => 'Campo não encontrado.'];
        }

        try {
            $this->dao->deletarCampo($campoId);

            // Registrar log
            $this->logAdminDao->registrar($usuarioId, 'automacao_config.campo_deletado', 'tb_automacao_payload_campos', $campoId, null);

            return ['sucesso' => true, 'mensagem' => null];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => 'Erro ao deletar campo: ' . $e->getMessage()];
        }
    }

    // ========================================================================
    // REGRAS DE TRANSFORMAÇÃO - OPERAÇÕES
    // ========================================================================

    /**
     * Buscar todas as regras de uma automação
     */
    public function buscarRegras(int $automacaoId): array
    {
        return $this->dao->buscarRegras($automacaoId);
    }

    /**
     * Buscar regras de um campo específico
     */
    public function buscarRegrasPorCampo(int $campoId): array
    {
        return $this->dao->buscarRegrasPorCampo($campoId);
    }

    /**
     * Criar nova regra de transformação
     * 
     * @return array{sucesso: bool, mensagem: ?string, id: ?int}
     */
    public function criarRegra(
        int $automacaoId,
        string $tipoRegra,
        array $configuracao,
        ?int $campoId = null,
        int $ordemExecucao = 10,
        bool $ativo = true,
        int $usuarioId = 0
    ): array {
        // Validar tipo de regra
        $tiposValidos = ['fixed_value', 'map_from_banco', 'timestamp', 'uuid', 'expression', 'concatenate', 'if_condition'];
        if (!in_array($tipoRegra, $tiposValidos, true)) {
            return ['sucesso' => false, 'mensagem' => "Tipo de regra '{$tipoRegra}' não é válido.", 'id' => null];
        }

        // Validar configuração por tipo
        $validacao = $this->validarConfiguracaoRegra($tipoRegra, $configuracao);
        if (!$validacao['valido']) {
            return ['sucesso' => false, 'mensagem' => $validacao['mensagem'], 'id' => null];
        }

        try {
            $id = $this->dao->criarRegra(
                $automacaoId,
                $tipoRegra,
                $configuracao,
                $campoId,
                $ordemExecucao,
                $ativo
            );

            // Registrar log
            $this->logAdminDao->registrar($usuarioId, 'automacao_config.regra_criada', 'tb_automacao_payload_regras', $id, null);

            return ['sucesso' => true, 'mensagem' => null, 'id' => $id];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => 'Erro ao criar regra: ' . $e->getMessage(), 'id' => null];
        }
    }

    /**
     * Atualizar regra de transformação
     */
    public function atualizarRegra(
        int $regraId,
        string $tipoRegra,
        array $configuracao,
        int $ordemExecucao = 10,
        bool $ativo = true,
        int $usuarioId = 0
    ): array {
        // Validar tipo de regra
        $tiposValidos = ['fixed_value', 'map_from_banco', 'timestamp', 'uuid', 'expression', 'concatenate', 'if_condition'];
        if (!in_array($tipoRegra, $tiposValidos, true)) {
            return ['sucesso' => false, 'mensagem' => "Tipo de regra '{$tipoRegra}' não é válido."];
        }

        // Validar configuração
        $validacao = $this->validarConfiguracaoRegra($tipoRegra, $configuracao);
        if (!$validacao['valido']) {
            return ['sucesso' => false, 'mensagem' => $validacao['mensagem']];
        }

        try {
            $this->dao->atualizarRegra($regraId, $tipoRegra, $configuracao, $ordemExecucao, $ativo);

            // Registrar log
            $this->logAdminDao->registrar($usuarioId, 'automacao_config.regra_atualizada', 'tb_automacao_payload_regras', $regraId, null);

            return ['sucesso' => true, 'mensagem' => null];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => 'Erro ao atualizar regra: ' . $e->getMessage()];
        }
    }

    /**
     * Deletar regra
     */
    public function deletarRegra(int $regraId, int $usuarioId = 0): array
    {
        try {
            $this->dao->deletarRegra($regraId);

            // Registrar log
            $this->logAdminDao->registrar($usuarioId, 'automacao_config.regra_deletada', 'tb_automacao_payload_regras', $regraId, null);

            return ['sucesso' => true, 'mensagem' => null];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => 'Erro ao deletar regra: ' . $e->getMessage()];
        }
    }

    // ========================================================================
    // HEADERS CUSTOMIZÁVEIS - OPERAÇÕES
    // ========================================================================

    /**
     * Buscar todos os headers de uma automação
     */
    public function buscarHeaders(int $automacaoId): array
    {
        return $this->dao->buscarHeaders($automacaoId);
    }

    /**
     * Criar novo header customizado
     * 
     * @return array{sucesso: bool, mensagem: ?string, id: ?int}
     */
    public function criarHeader(
        int $automacaoId,
        string $chave,
        string $valor,
        int $posicao = 10,
        bool $valorDinamico = false,
        ?int $regraId = null,
        int $usuarioId = 0
    ): array {
        // Validar chave do header
        if (empty(trim($chave))) {
            return ['sucesso' => false, 'mensagem' => 'Chave do header não pode estar vazia.', 'id' => null];
        }

        if (!preg_match('/^[a-zA-Z0-9\-]+$/', $chave)) {
            return ['sucesso' => false, 'mensagem' => 'Chave do header só pode conter letras, números e hífens.', 'id' => null];
        }

        try {
            $id = $this->dao->criarHeader($automacaoId, $chave, $valor, $posicao, $valorDinamico, $regraId);

            // Registrar log
            $this->logAdminDao->registrar($usuarioId, 'automacao_config.header_criado', 'tb_automacao_webhook_headers', $id, null);

            return ['sucesso' => true, 'mensagem' => null, 'id' => $id];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => 'Erro ao criar header: ' . $e->getMessage(), 'id' => null];
        }
    }

    /**
     * Atualizar header customizado
     */
    public function atualizarHeader(
        int $headerId,
        string $valor,
        int $posicao = 10,
        bool $valorDinamico = false,
        ?int $regraId = null,
        int $usuarioId = 0
    ): array {
        try {
            $this->dao->atualizarHeader($headerId, $valor, $posicao, $valorDinamico, $regraId);

            // Registrar log
            $this->logAdminDao->registrar($usuarioId, 'automacao_config.header_atualizado', 'tb_automacao_webhook_headers', $headerId, null);

            return ['sucesso' => true, 'mensagem' => null];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => 'Erro ao atualizar header: ' . $e->getMessage()];
        }
    }

    /**
     * Deletar header
     */
    public function deletarHeader(int $headerId, int $usuarioId = 0): array
    {
        try {
            $this->dao->deletarHeader($headerId);

            // Registrar log
            $this->logAdminDao->registrar($usuarioId, 'automacao_config.header_deletado', 'tb_automacao_webhook_headers', $headerId, null);

            return ['sucesso' => true, 'mensagem' => null];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => 'Erro ao deletar header: ' . $e->getMessage()];
        }
    }

    // ========================================================================
    // LOG DE WEBHOOKS
    // ========================================================================

    /**
     * Registrar execução de webhook
     */
    public function registrarLog(
        int $automacaoId,
        string $urlEnviado,
        string $metodoHttp,
        array $headersEnviado,
        array $payloadEnviado,
        int $httpStatus,
        array $headersResposta,
        ?array $respostaWebhook = null,
        ?string $respostaTexto = null,
        int $tempoExecucaoMs = 0,
        ?string $erroTipo = null,
        ?string $erroMensagem = null,
        ?int $clienteId = null
    ): int {
        return $this->dao->registrarLog(
            $automacaoId,
            $urlEnviado,
            $metodoHttp,
            $headersEnviado,
            $payloadEnviado,
            $httpStatus,
            $headersResposta,
            $respostaWebhook,
            $respostaTexto,
            $tempoExecucaoMs,
            $erroTipo,
            $erroMensagem,
            $clienteId
        );
    }

    /**
     * Buscar logs de uma automação
     */
    public function buscarLogs(int $automacaoId, int $limite = 50): array
    {
        return $this->dao->buscarLogs($automacaoId, $limite);
    }

    /**
     * Buscar logs com erro
     */
    public function buscarLogsComErro(int $automacaoId, int $limite = 50): array
    {
        return $this->dao->buscarLogsComErro($automacaoId, $limite);
    }

    /**
     * Obter estatísticas de webhooks
     */
    public function obterEstatisticas(int $automacaoId): array
    {
        return $this->dao->obterEstatisticas($automacaoId);
    }

    // ========================================================================
    // CONFIGURAÇÃO COMPLETA
    // ========================================================================

    /**
     * Buscar configuração COMPLETA de uma automação
     */
    public function buscarConfigCompleta(int $automacaoId): array
    {
        return $this->dao->buscarConfigCompleta($automacaoId);
    }

    /**
     * Deletar TODA configuração de uma automação
     */
    public function deletarConfigCompleta(int $automacaoId, int $usuarioId = 0): array
    {
        try {
            $this->dao->deletarConfigCompleta($automacaoId);

            // Registrar log
            $this->logAdminDao->registrar($usuarioId, 'automacao_config.config_deletada_completa', 'tb_automacao_payload_campos', $automacaoId, null);

            return ['sucesso' => true, 'mensagem' => null];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => 'Erro ao deletar configuração: ' . $e->getMessage()];
        }
    }

    // ========================================================================
    // VALIDAÇÕES INTERNAS
    // ========================================================================

    /**
     * Validar valor baseado no tipo de dado
     */
    private function validarValorPorTipo(string $valor, string $tipo): array
    {
        switch ($tipo) {
            case 'integer':
                if (!is_numeric($valor)) {
                    return ['valido' => false, 'mensagem' => 'Valor padrão deve ser um número inteiro.'];
                }
                break;

            case 'decimal':
                if (!is_numeric($valor)) {
                    return ['valido' => false, 'mensagem' => 'Valor padrão deve ser um número decimal.'];
                }
                break;

            case 'boolean':
                if (!in_array($valor, ['true', 'false', '0', '1'], true)) {
                    return ['valido' => false, 'mensagem' => 'Valor padrão deve ser true, false, 0 ou 1.'];
                }
                break;

            case 'email':
                if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                    return ['valido' => false, 'mensagem' => 'Valor padrão deve ser um email válido.'];
                }
                break;

            case 'uuid':
                if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $valor)) {
                    return ['valido' => false, 'mensagem' => 'Valor padrão deve ser um UUID v4 válido.'];
                }
                break;

            case 'date':
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) || !strtotime($valor)) {
                    return ['valido' => false, 'mensagem' => 'Valor padrão deve estar no formato YYYY-MM-DD.'];
                }
                break;

            case 'time':
                if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $valor)) {
                    return ['valido' => false, 'mensagem' => 'Valor padrão deve estar no formato HH:MM:SS.'];
                }
                break;
        }

        return ['valido' => true];
    }

    /**
     * Validar configuração de regra por tipo
     */
    private function validarConfiguracaoRegra(string $tipoRegra, array $configuracao): array
    {
        switch ($tipoRegra) {
            case 'fixed_value':
                if (!isset($configuracao['valor'])) {
                    return ['valido' => false, 'mensagem' => 'fixed_value requer campo "valor".'];
                }
                break;

            case 'map_from_banco':
                if (!isset($configuracao['tabela'], $configuracao['coluna'])) {
                    return ['valido' => false, 'mensagem' => 'map_from_banco requer "tabela" e "coluna".'];
                }
                break;

            case 'expression':
                if (!isset($configuracao['codigo'])) {
                    return ['valido' => false, 'mensagem' => 'expression requer campo "codigo".'];
                }
                break;

            case 'concatenate':
                if (!isset($configuracao['campos']) || !is_array($configuracao['campos'])) {
                    return ['valido' => false, 'mensagem' => 'concatenate requer array "campos".'];
                }
                break;

            case 'if_condition':
                if (!isset($configuracao['condicao'], $configuracao['valor_true'], $configuracao['valor_false'])) {
                    return ['valido' => false, 'mensagem' => 'if_condition requer "condicao", "valor_true" e "valor_false".'];
                }
                break;
        }

        return ['valido' => true];
    }
}