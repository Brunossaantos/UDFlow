<?php

namespace Udflow\dao;

use Udflow\config\Conexao;
use PDO;

/**
 * AutomacaoConfigDao
 * 
 * Gerencia todas as operações de banco de dados relacionadas a:
 * - Campos customizáveis do payload
 * - Regras de transformação
 * - Headers customizáveis
 * - Logs de webhook
 * - Histórico de configurações
 * - Testes de payload
 */
class AutomacaoConfigDao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::pegar();
    }

    // ========================================================================
    // CAMPOS DO PAYLOAD
    // ========================================================================

    /**
     * Buscar todos os campos de uma automação (ordenado por posição)
     */
    public function buscarCampos(int $automacaoId): array
    {
        $sql = 'SELECT * FROM tb_automacao_payload_campos 
                WHERE automacao_id = :automacao_id 
                ORDER BY posicao ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar um campo específico
     */
    public function buscarCampo(int $campoId): ?array
    {
        $sql = 'SELECT * FROM tb_automacao_payload_campos WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $campoId, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    /**
     * Criar novo campo de payload
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
        ?array $validacaoCustomizada = null
    ): int {
        $sql = 'INSERT INTO tb_automacao_payload_campos 
                (automacao_id, nome_campo, label_campo, tipo_dado, obrigatorio, valor_padrao, validacao_customizada, posicao, descricao) 
                VALUES (:automacao_id, :nome_campo, :label_campo, :tipo_dado, :obrigatorio, :valor_padrao, :validacao_customizada, :posicao, :descricao)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $stmt->bindValue(':nome_campo', $nomeCampo, PDO::PARAM_STR);
        $stmt->bindValue(':label_campo', $labelCampo, PDO::PARAM_STR);
        $stmt->bindValue(':tipo_dado', $tipoDado, PDO::PARAM_STR);
        $stmt->bindValue(':obrigatorio', $obrigatorio ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':valor_padrao', $valorPadrao, PDO::PARAM_STR);
        $stmt->bindValue(':validacao_customizada', $validacaoCustomizada ? json_encode($validacaoCustomizada) : null, PDO::PARAM_STR);
        $stmt->bindValue(':posicao', $posicao, PDO::PARAM_INT);
        $stmt->bindValue(':descricao', $descricao, PDO::PARAM_STR);

        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
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
        ?array $validacaoCustomizada = null
    ): bool {
        $sql = 'UPDATE tb_automacao_payload_campos 
                SET tipo_dado = :tipo_dado, 
                    obrigatorio = :obrigatorio, 
                    valor_padrao = :valor_padrao, 
                    validacao_customizada = :validacao_customizada, 
                    posicao = :posicao, 
                    label_campo = :label_campo, 
                    descricao = :descricao 
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':tipo_dado', $tipoDado, PDO::PARAM_STR);
        $stmt->bindValue(':obrigatorio', $obrigatorio ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':valor_padrao', $valorPadrao, PDO::PARAM_STR);
        $stmt->bindValue(':validacao_customizada', $validacaoCustomizada ? json_encode($validacaoCustomizada) : null, PDO::PARAM_STR);
        $stmt->bindValue(':posicao', $posicao, PDO::PARAM_INT);
        $stmt->bindValue(':label_campo', $labelCampo, PDO::PARAM_STR);
        $stmt->bindValue(':descricao', $descricao, PDO::PARAM_STR);
        $stmt->bindValue(':id', $campoId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Deletar campo de payload (e suas regras)
     */
    public function deletarCampo(int $campoId): bool
    {
        $sql = 'DELETE FROM tb_automacao_payload_campos WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $campoId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Deletar todos os campos de uma automação
     */
    public function deletarCamposPorAutomacao(int $automacaoId): bool
    {
        $sql = 'DELETE FROM tb_automacao_payload_campos WHERE automacao_id = :automacao_id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // ========================================================================
    // REGRAS DE TRANSFORMAÇÃO
    // ========================================================================

    /**
     * Buscar todas as regras de uma automação (ordenado por execução)
     */
    public function buscarRegras(int $automacaoId): array
    {
        $sql = 'SELECT * FROM tb_automacao_payload_regras 
                WHERE automacao_id = :automacao_id AND ativo = 1
                ORDER BY ordem_execucao ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar regras de um campo específico
     */
    public function buscarRegrasPorCampo(int $campoId): array
    {
        $sql = 'SELECT * FROM tb_automacao_payload_regras 
                WHERE campo_id = :campo_id AND ativo = 1
                ORDER BY ordem_execucao ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':campo_id', $campoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar uma regra específica
     */
    public function buscarRegra(int $regraId): ?array
    {
        $sql = 'SELECT * FROM tb_automacao_payload_regras WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $regraId, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    /**
     * Criar nova regra de transformação
     */
    public function criarRegra(
        int $automacaoId,
        string $tipoRegra,
        array $configuracao,
        ?int $campoId = null,
        int $ordemExecucao = 10,
        bool $ativo = true
    ): int {
        $sql = 'INSERT INTO tb_automacao_payload_regras 
                (automacao_id, campo_id, tipo_regra, configuracao, ativo, ordem_execucao) 
                VALUES (:automacao_id, :campo_id, :tipo_regra, :configuracao, :ativo, :ordem_execucao)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $stmt->bindValue(':campo_id', $campoId, $campoId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':tipo_regra', $tipoRegra, PDO::PARAM_STR);
        $stmt->bindValue(':configuracao', json_encode($configuracao), PDO::PARAM_STR);
        $stmt->bindValue(':ativo', $ativo ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':ordem_execucao', $ordemExecucao, PDO::PARAM_INT);

        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Atualizar regra de transformação
     */
    public function atualizarRegra(
        int $regraId,
        string $tipoRegra,
        array $configuracao,
        int $ordemExecucao = 10,
        bool $ativo = true
    ): bool {
        $sql = 'UPDATE tb_automacao_payload_regras 
                SET tipo_regra = :tipo_regra, 
                    configuracao = :configuracao, 
                    ativo = :ativo, 
                    ordem_execucao = :ordem_execucao 
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':tipo_regra', $tipoRegra, PDO::PARAM_STR);
        $stmt->bindValue(':configuracao', json_encode($configuracao), PDO::PARAM_STR);
        $stmt->bindValue(':ativo', $ativo ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':ordem_execucao', $ordemExecucao, PDO::PARAM_INT);
        $stmt->bindValue(':id', $regraId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Deletar regra de transformação
     */
    public function deletarRegra(int $regraId): bool
    {
        $sql = 'DELETE FROM tb_automacao_payload_regras WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $regraId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Deletar todas as regras de uma automação
     */
    public function deletarRegrasPorAutomacao(int $automacaoId): bool
    {
        $sql = 'DELETE FROM tb_automacao_payload_regras WHERE automacao_id = :automacao_id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // ========================================================================
    // HEADERS CUSTOMIZÁVEIS
    // ========================================================================

    /**
     * Buscar todos os headers de uma automação (ordenado por posição)
     */
    public function buscarHeaders(int $automacaoId): array
    {
        $sql = 'SELECT * FROM tb_automacao_webhook_headers 
                WHERE automacao_id = :automacao_id 
                ORDER BY posicao ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Criar novo header customizado
     */
    public function criarHeader(
        int $automacaoId,
        string $chave,
        string $valor,
        int $posicao = 10,
        bool $valorDinamico = false,
        ?int $regraId = null
    ): int {
        $sql = 'INSERT INTO tb_automacao_webhook_headers 
                (automacao_id, chave, valor, valor_dinamico, regra_id, posicao) 
                VALUES (:automacao_id, :chave, :valor, :valor_dinamico, :regra_id, :posicao)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $stmt->bindValue(':chave', $chave, PDO::PARAM_STR);
        $stmt->bindValue(':valor', $valor, PDO::PARAM_STR);
        $stmt->bindValue(':valor_dinamico', $valorDinamico ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':regra_id', $regraId, $regraId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':posicao', $posicao, PDO::PARAM_INT);

        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Atualizar header customizado
     */
    public function atualizarHeader(
        int $headerId,
        string $valor,
        int $posicao = 10,
        bool $valorDinamico = false,
        ?int $regraId = null
    ): bool {
        $sql = 'UPDATE tb_automacao_webhook_headers 
                SET valor = :valor, 
                    valor_dinamico = :valor_dinamico, 
                    regra_id = :regra_id, 
                    posicao = :posicao 
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':valor', $valor, PDO::PARAM_STR);
        $stmt->bindValue(':valor_dinamico', $valorDinamico ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':regra_id', $regraId, $regraId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':posicao', $posicao, PDO::PARAM_INT);
        $stmt->bindValue(':id', $headerId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Deletar header customizado
     */
    public function deletarHeader(int $headerId): bool
    {
        $sql = 'DELETE FROM tb_automacao_webhook_headers WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $headerId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Deletar todos os headers de uma automação
     */
    public function deletarHeadersPorAutomacao(int $automacaoId): bool
    {
        $sql = 'DELETE FROM tb_automacao_webhook_headers WHERE automacao_id = :automacao_id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // ========================================================================
    // LOG DE WEBHOOKS
    // ========================================================================

    /**
     * Registrar requisição de webhook
     */
    public function registrarLog(
        int $automacaoId,
        string $urlEnviado,
        string $metodoHttp,
        array $headersEnviado,
        array $payloadEnviado,
        int $httpStatus,
        array $headersResposta,
        ?array $respostaWebhook,
        ?string $respostaTexto,
        int $tempoExecucaoMs,
        ?string $erroTipo = null,
        ?string $erroMensagem = null,
        ?int $clienteId = null
    ): int {
        $sql = 'INSERT INTO tb_automacao_webhook_log 
                (automacao_id, url_enviado, metodo_http, headers_enviado, payload_enviado, 
                 http_status, headers_resposta, resposta_webhook, resposta_texto, tempo_execucao_ms, 
                 erro_tipo, erro_mensagem, cliente_id) 
                VALUES 
                (:automacao_id, :url_enviado, :metodo_http, :headers_enviado, :payload_enviado, 
                 :http_status, :headers_resposta, :resposta_webhook, :resposta_texto, :tempo_execucao_ms, 
                 :erro_tipo, :erro_mensagem, :cliente_id)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $stmt->bindValue(':url_enviado', $urlEnviado, PDO::PARAM_STR);
        $stmt->bindValue(':metodo_http', $metodoHttp, PDO::PARAM_STR);
        $stmt->bindValue(':headers_enviado', json_encode($headersEnviado), PDO::PARAM_STR);
        $stmt->bindValue(':payload_enviado', json_encode($payloadEnviado), PDO::PARAM_STR);
        $stmt->bindValue(':http_status', $httpStatus, PDO::PARAM_INT);
        $stmt->bindValue(':headers_resposta', json_encode($headersResposta), PDO::PARAM_STR);
        $stmt->bindValue(':resposta_webhook', $respostaWebhook ? json_encode($respostaWebhook) : null, PDO::PARAM_STR);
        $stmt->bindValue(':resposta_texto', $respostaTexto, PDO::PARAM_STR);
        $stmt->bindValue(':tempo_execucao_ms', $tempoExecucaoMs, PDO::PARAM_INT);
        $stmt->bindValue(':erro_tipo', $erroTipo, PDO::PARAM_STR);
        $stmt->bindValue(':erro_mensagem', $erroMensagem, PDO::PARAM_STR);
        $stmt->bindValue(':cliente_id', $clienteId, $clienteId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Buscar logs de uma automação (últimos N)
     */
    public function buscarLogs(int $automacaoId, int $limite = 50): array
    {
        $sql = 'SELECT * FROM tb_automacao_webhook_log 
                WHERE automacao_id = :automacao_id 
                ORDER BY criado_em DESC 
                LIMIT :limite';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar logs com erro
     */
    public function buscarLogsComErro(int $automacaoId, int $limite = 50): array
    {
        $sql = 'SELECT * FROM tb_automacao_webhook_log 
                WHERE automacao_id = :automacao_id AND erro_tipo IS NOT NULL
                ORDER BY criado_em DESC 
                LIMIT :limite';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Obter estatísticas de webhooks
     */
    public function obterEstatisticas(int $automacaoId): array
    {
        $sql = 'SELECT 
                  COUNT(*) as total_execucoes,
                  SUM(CASE WHEN http_status = 200 THEN 1 ELSE 0 END) as sucessos,
                  SUM(CASE WHEN http_status != 200 THEN 1 ELSE 0 END) as erros,
                  AVG(tempo_execucao_ms) as tempo_medio_ms,
                  MIN(tempo_execucao_ms) as tempo_min_ms,
                  MAX(tempo_execucao_ms) as tempo_max_ms,
                  MAX(criado_em) as ultima_execucao
                FROM tb_automacao_webhook_log
                WHERE automacao_id = :automacao_id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // ========================================================================
    // HISTÓRICO DE CONFIGURAÇÕES
    // ========================================================================

    /**
     * Registrar mudança de configuração (auditoria)
     */
    public function registrarHistorico(
        int $automacaoId,
        int $usuarioId,
        string $tabelaAfetada,
        string $tipoOperacao,
        ?array $configuracaoAnterior = null,
        ?array $configuracaoNova = null,
        ?string $descricao = null,
        ?int $registroId = null
    ): int {
        $sql = 'INSERT INTO tb_automacao_config_historico 
                (automacao_id, usuario_id, tabela_afetada, registro_id, tipo_operacao, configuracao_anterior, configuracao_nova, descricao) 
                VALUES (:automacao_id, :usuario_id, :tabela_afetada, :registro_id, :tipo_operacao, :configuracao_anterior, :configuracao_nova, :descricao)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':tabela_afetada', $tabelaAfetada, PDO::PARAM_STR);
        $stmt->bindValue(':registro_id', $registroId, $registroId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':tipo_operacao', $tipoOperacao, PDO::PARAM_STR);
        $stmt->bindValue(':configuracao_anterior', $configuracaoAnterior ? json_encode($configuracaoAnterior) : null, PDO::PARAM_STR);
        $stmt->bindValue(':configuracao_nova', $configuracaoNova ? json_encode($configuracaoNova) : null, PDO::PARAM_STR);
        $stmt->bindValue(':descricao', $descricao, PDO::PARAM_STR);

        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Buscar histórico de uma automação
     */
    public function buscarHistorico(int $automacaoId, int $limite = 100): array
    {
        $sql = 'SELECT h.*, u.nome as usuario_nome
                FROM tb_automacao_config_historico h
                LEFT JOIN tb_usuarios u ON h.usuario_id = u.id
                WHERE h.automacao_id = :automacao_id 
                ORDER BY h.criado_em DESC 
                LIMIT :limite';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // ========================================================================
    // TESTES DE PAYLOAD
    // ========================================================================

    /**
     * Registrar teste de payload
     */
    public function registrarTeste(
        int $automacaoId,
        int $usuarioId,
        array $dadosEntrada,
        array $payloadGerado,
        bool $sucesso = true,
        ?string $erroMensagem = null
    ): int {
        $sql = 'INSERT INTO tb_automacao_payload_testes 
                (automacao_id, usuario_id, dados_entrada, payload_gerado, sucesso, erro_mensagem) 
                VALUES (:automacao_id, :usuario_id, :dados_entrada, :payload_gerado, :sucesso, :erro_mensagem)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':dados_entrada', json_encode($dadosEntrada), PDO::PARAM_STR);
        $stmt->bindValue(':payload_gerado', json_encode($payloadGerado), PDO::PARAM_STR);
        $stmt->bindValue(':sucesso', $sucesso ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':erro_mensagem', $erroMensagem, PDO::PARAM_STR);

        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Buscar testes de uma automação
     */
    public function buscarTestes(int $automacaoId, int $limite = 20): array
    {
        $sql = 'SELECT t.*, u.nome as usuario_nome
                FROM tb_automacao_payload_testes t
                LEFT JOIN tb_usuarios u ON t.usuario_id = u.id
                WHERE t.automacao_id = :automacao_id 
                ORDER BY t.criado_em DESC 
                LIMIT :limite';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // ========================================================================
    // CONFIGURAÇÃO COMPLETA
    // ========================================================================

    /**
     * Buscar configuração COMPLETA de uma automação
     */
    public function buscarConfigCompleta(int $automacaoId): array
    {
        return [
            'campos' => $this->buscarCampos($automacaoId),
            'regras' => $this->buscarRegras($automacaoId),
            'headers' => $this->buscarHeaders($automacaoId),
        ];
    }

    /**
     * Deletar TUDO da configuração de uma automação
     */
    public function deletarConfigCompleta(int $automacaoId): bool
    {
        try {
            $this->pdo->beginTransaction();

            $this->deletarHeadersPorAutomacao($automacaoId);
            $this->deletarRegrasPorAutomacao($automacaoId);
            $this->deletarCamposPorAutomacao($automacaoId);

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}