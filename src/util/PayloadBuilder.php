<?php

namespace Udflow\util;

use Udflow\dao\AutomacaoConfigDao;
use Udflow\config\Conexao;
use PDO;

/**
 * PayloadBuilder
 * 
 * Constrói payloads JSON dinâmicos com base nas configurações de automações.
 * Aplica transformações (map_from_banco, timestamp, uuid, expressions, etc).
 */
class PayloadBuilder
{
    private AutomacaoConfigDao $dao;
    private PDO $pdo;
    private array $campos = [];
    private array $regras = [];
    private array $dadosEntrada = [];
    private array $payload = [];
    private array $erros = [];

    public function __construct()
    {
        $this->dao = new AutomacaoConfigDao();
        $this->pdo = Conexao::pegar();
    }

    /**
     * Inicializar construtor com configuração de automação
     */
    public function inicializar(int $automacaoId, array $dadosEntrada = []): self
    {
        $this->campos = $this->dao->buscarCampos($automacaoId);
        $this->regras = $this->dao->buscarRegras($automacaoId);
        $this->dadosEntrada = $dadosEntrada;
        $this->payload = [];
        $this->erros = [];

        return $this;
    }

    /**
     * Construir o payload completo
     */
    public function construir(): array
    {
        if (empty($this->campos)) {
            return ['payload' => [], 'sucesso' => true, 'erros' => []];
        }

        // Processar cada campo em ordem de posição
        foreach ($this->campos as $campo) {
            $this->processarCampo($campo);
        }

        return [
            'payload' => $this->payload,
            'sucesso' => empty($this->erros),
            'erros' => $this->erros,
        ];
    }

    /**
     * Processar um campo individual
     */
    private function processarCampo(array $campo): void
    {
        $nomeCampo = $campo['nome_campo'];
        $tipoDado = $campo['tipo_dado'];
        $obrigatorio = (bool) $campo['obrigatorio'];
        $valorPadrao = $campo['valor_padrao'];

        // Buscar regras específicas deste campo
        $regrasDosCampo = array_filter($this->regras, fn($r) => $r['campo_id'] === (int) $campo['id']);

        $valor = null;

        // Se tem regras específicas, aplicar em ordem
        if (!empty($regrasDosCampo)) {
            usort($regrasDosCampo, fn($a, $b) => $a['ordem_execucao'] <=> $b['ordem_execucao']);

            foreach ($regrasDosCampo as $regra) {
                if (!(bool) $regra['ativo']) continue;

                $resultado = $this->aplicarRegra($regra, $valor);
                if ($resultado['sucesso']) {
                    $valor = $resultado['valor'];
                } else {
                    $this->erros[] = "Campo '{$nomeCampo}': {$resultado['erro']}";
                    if ($obrigatorio) {
                        return;
                    }
                }
            }
        }

        // Se não tem valor, tentar dados de entrada
        if ($valor === null && isset($this->dadosEntrada[$nomeCampo])) {
            $valor = $this->dadosEntrada[$nomeCampo];
        }

        // Se ainda não tem valor, usar padrão
        if ($valor === null && $valorPadrao !== null) {
            $valor = $valorPadrao;
        }

        // Validar obrigatoriedade
        if ($valor === null && $obrigatorio) {
            $this->erros[] = "Campo obrigatório '{$nomeCampo}' não foi fornecido.";
            return;
        }

        // Converter para tipo correto
        if ($valor !== null) {
            $conversao = $this->converterTipo($valor, $tipoDado);
            if (!$conversao['sucesso']) {
                $this->erros[] = "Campo '{$nomeCampo}': {$conversao['erro']}";
                return;
            }
            $valor = $conversao['valor'];

            // Validar contra regras customizadas
            if (!empty($campo['validacao_customizada'])) {
                $validacao = json_decode($campo['validacao_customizada'], true);
                if (is_array($validacao)) {
                    $validacaoResult = PayloadValidator::validarCampo($valor, $tipoDado, $validacao);
                    if (!$validacaoResult['valido']) {
                        $this->erros[] = "Campo '{$nomeCampo}': {$validacaoResult['mensagem']}";
                        return;
                    }
                }
            }
        }

        // Adicionar ao payload se tiver valor
        if ($valor !== null) {
            $this->payload[$nomeCampo] = $valor;
        }
    }

    /**
     * Aplicar uma regra de transformação
     */
    private function aplicarRegra(array $regra, $valorAnterior = null): array
    {
        $tipoRegra = $regra['tipo_regra'];
        $config = json_decode($regra['configuracao'], true) ?: [];

        try {
            switch ($tipoRegra) {
                case 'fixed_value':
                    return $this->regraFixedValue($config);

                case 'map_from_banco':
                    return $this->regraMapFromBanco($config);

                case 'timestamp':
                    return $this->regraTimestamp($config);

                case 'uuid':
                    return $this->regraUuid($config);

                case 'expression':
                    return $this->regraExpression($config);

                case 'concatenate':
                    return $this->regraConcatenate($config);

                case 'if_condition':
                    return $this->regraIfCondition($config);

                default:
                    return ['sucesso' => false, 'erro' => "Tipo de regra '{$tipoRegra}' desconhecido."];
            }
        } catch (\Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Regra: fixed_value - valor fixo
     */
    private function regraFixedValue(array $config): array
    {
        $valor = $config['valor'] ?? null;
        return ['sucesso' => true, 'valor' => $valor];
    }

    /**
     * Regra: map_from_banco - buscar do banco
     */
    private function regraMapFromBanco(array $config): array
    {
        $tabela = $config['tabela'] ?? null;
        $coluna = $config['coluna'] ?? null;
        $condicao = $config['condicao'] ?? null;

        if (!$tabela || !$coluna) {
            return ['sucesso' => false, 'erro' => 'Configuração incompleta de map_from_banco.'];
        }

        // Preparar condição com dados de entrada
        $condicaoPreparada = $condicao;
        foreach ($this->dadosEntrada as $chave => $valor) {
            $condicaoPreparada = str_replace(':' . $chave, '?', $condicaoPreparada);
        }

        $sql = "SELECT {$coluna} FROM {$tabela} WHERE {$condicao}";

        try {
            $stmt = $this->pdo->prepare($sql);

            // Bind dos parâmetros da condição
            $parametros = array_values($this->dadosEntrada);
            $stmt->execute($parametros);

            $resultado = $stmt->fetchColumn();
            return ['sucesso' => true, 'valor' => $resultado];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'erro' => "Erro ao buscar do banco: {$e->getMessage()}"];
        }
    }

    /**
     * Regra: timestamp - data/hora atual
     */
    private function regraTimestamp(array $config): array
    {
        $formato = $config['formato'] ?? 'Y-m-d H:i:s';

        try {
            $valor = date($formato);
            return ['sucesso' => true, 'valor' => $valor];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'erro' => "Formato de timestamp inválido: {$e->getMessage()}"];
        }
    }

    /**
     * Regra: uuid - gerar UUID
     */
    private function regraUuid(array $config): array
    {
        $versao = $config['versao'] ?? 4;

        if ($versao === 4) {
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            );

            return ['sucesso' => true, 'valor' => $uuid];
        }

        return ['sucesso' => false, 'erro' => "UUID versão {$versao} não suportada."];
    }

    /**
     * Regra: expression - executar expressão PHP
     */
    private function regraExpression(array $config): array
    {
        $codigo = $config['codigo'] ?? null;

        if (!$codigo) {
            return ['sucesso' => false, 'erro' => 'Código de expression vazio.'];
        }

        try {
            // Criar escopo com dados de entrada como variáveis
            extract($this->dadosEntrada, EXTR_PREFIX_ALL, 'input');
            extract($this->payload, EXTR_PREFIX_ALL, 'payload');

            $valor = eval("return {$codigo}");
            return ['sucesso' => true, 'valor' => $valor];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'erro' => "Erro ao executar expressão: {$e->getMessage()}"];
        }
    }

    /**
     * Regra: concatenate - juntar múltiplos valores
     */
    private function regraConcatenate(array $config): array
    {
        $campos = $config['campos'] ?? [];

        if (empty($campos)) {
            return ['sucesso' => false, 'erro' => 'Lista de campos vazia em concatenate.'];
        }

        $resultado = '';
        foreach ($campos as $item) {
            if (isset($this->dadosEntrada[$item])) {
                $resultado .= $this->dadosEntrada[$item];
            } elseif (isset($this->payload[$item])) {
                $resultado .= $this->payload[$item];
            } else {
                $resultado .= $item; // Se não encontrar, usa o literal
            }
        }

        return ['sucesso' => true, 'valor' => $resultado];
    }

    /**
     * Regra: if_condition - condicional
     */
    private function regraIfCondition(array $config): array
    {
        $condicao = $config['condicao'] ?? null;
        $valorTrue = $config['valor_true'] ?? null;
        $valorFalse = $config['valor_false'] ?? null;

        if (!$condicao) {
            return ['sucesso' => false, 'erro' => 'Condição vazia em if_condition.'];
        }

        try {
            // Criar escopo com dados de entrada
            extract($this->dadosEntrada, EXTR_PREFIX_ALL, 'input');
            extract($this->payload, EXTR_PREFIX_ALL, 'payload');

            $resultado = eval("return {$condicao};");
            $valor = $resultado ? $valorTrue : $valorFalse;

            return ['sucesso' => true, 'valor' => $valor];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'erro' => "Erro ao avaliar condição: {$e->getMessage()}"];
        }
    }

    /**
     * Converter valor para o tipo correto
     */
    private function converterTipo($valor, string $tipo): array
    {
        if ($valor === null) {
            return ['sucesso' => true, 'valor' => null];
        }

        try {
            switch ($tipo) {
                case 'integer':
                    return ['sucesso' => true, 'valor' => (int) $valor];

                case 'decimal':
                    return ['sucesso' => true, 'valor' => (float) $valor];

                case 'boolean':
                    if (is_bool($valor)) {
                        return ['sucesso' => true, 'valor' => $valor];
                    }
                    $valor = filter_var($valor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($valor === null) {
                        return ['sucesso' => false, 'erro' => 'Valor booleano inválido.'];
                    }
                    return ['sucesso' => true, 'valor' => $valor];

                case 'email':
                    if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                        return ['sucesso' => false, 'erro' => 'Email inválido.'];
                    }
                    return ['sucesso' => true, 'valor' => $valor];

                case 'json':
                    if (is_string($valor)) {
                        $decoded = json_decode($valor, true);
                        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                            return ['sucesso' => false, 'erro' => 'JSON inválido.'];
                        }
                        return ['sucesso' => true, 'valor' => $decoded];
                    }
                    return ['sucesso' => true, 'valor' => $valor];

                case 'array':
                    if (is_array($valor)) {
                        return ['sucesso' => true, 'valor' => $valor];
                    }
                    if (is_string($valor)) {
                        $decoded = json_decode($valor, true);
                        if (is_array($decoded)) {
                            return ['sucesso' => true, 'valor' => $decoded];
                        }
                    }
                    return ['sucesso' => false, 'erro' => 'Valor não é um array válido.'];

                case 'date':
                    $timestamp = strtotime($valor);
                    if ($timestamp === false) {
                        return ['sucesso' => false, 'erro' => 'Data inválida.'];
                    }
                    return ['sucesso' => true, 'valor' => date('Y-m-d', $timestamp)];

                case 'time':
                    $timestamp = strtotime($valor);
                    if ($timestamp === false) {
                        return ['sucesso' => false, 'erro' => 'Hora inválida.'];
                    }
                    return ['sucesso' => true, 'valor' => date('H:i:s', $timestamp)];

                case 'timestamp':
                    $timestamp = strtotime($valor);
                    if ($timestamp === false) {
                        return ['sucesso' => false, 'erro' => 'Timestamp inválido.'];
                    }
                    return ['sucesso' => true, 'valor' => date('Y-m-d H:i:s', $timestamp)];

                case 'uuid':
                    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $valor)) {
                        return ['sucesso' => false, 'erro' => 'UUID v4 inválido.'];
                    }
                    return ['sucesso' => true, 'valor' => $valor];

                case 'string':
                default:
                    return ['sucesso' => true, 'valor' => (string) $valor];
            }
        } catch (\Exception $e) {
            return ['sucesso' => false, 'erro' => "Erro ao converter tipo: {$e->getMessage()}"];
        }
    }

    /**
     * Obter payload construído
     */
    public function obterPayload(): array
    {
        return $this->payload;
    }

    /**
     * Obter erros
     */
    public function obterErros(): array
    {
        return $this->erros;
    }

    /**
     * Verificar se construção foi bem-sucedida
     */
    public function foiBemSucedida(): bool
    {
        return empty($this->erros);
    }
}
