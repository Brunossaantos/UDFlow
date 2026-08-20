<?php

namespace Udflow\util;

// Proteção contra redeclaração
if (class_exists('Udflow\util\PayloadValidator', false)) {
    return;
}

/**
 * PayloadValidator
 * 
 * Valida campos de payload contra regras customizadas.
 * Suporta validação de tipos, padrões, comprimento, emails @udlog, etc.
 */
class PayloadValidator
{
    /**
     * Validar um campo contra regras customizadas
     * 
     * @param mixed $valor O valor a validar
     * @param string $tipoDado O tipo de dado (string, email, etc)
     * @param array $regras Array de regras (padrão, min_length, max_length, etc)
     * 
     * @return array{valido: bool, mensagem: string}
     */
    public static function validarCampo($valor, string $tipoDado, array $regras = []): array
    {
        // Validar tipo base
        $validacaoTipo = self::validarTipo($valor, $tipoDado);
        if (!$validacaoTipo['valido']) {
            return $validacaoTipo;
        }

        // Validar regras customizadas
        foreach ($regras as $regra => $config) {
            $metodo = 'validar' . ucfirst(str_replace('_', '', $regra));

            if (method_exists(self::class, $metodo)) {
                $resultado = self::$metodo($valor, $config);
                if (!$resultado['valido']) {
                    return $resultado;
                }
            }
        }

        return ['valido' => true, 'mensagem' => null];
    }

    /**
     * Validar tipo base de um valor
     */
    public static function validarTipo($valor, string $tipo): array
    {
        switch ($tipo) {
            case 'string':
                if (!is_string($valor)) {
                    return ['valido' => false, 'mensagem' => 'Valor deve ser uma string.'];
                }
                break;

            case 'integer':
                if (!is_int($valor) && !is_numeric($valor)) {
                    return ['valido' => false, 'mensagem' => 'Valor deve ser um inteiro.'];
                }
                break;

            case 'decimal':
            case 'float':
                if (!is_float($valor) && !is_numeric($valor)) {
                    return ['valido' => false, 'mensagem' => 'Valor deve ser um decimal.'];
                }
                break;

            case 'boolean':
                if (!is_bool($valor)) {
                    return ['valido' => false, 'mensagem' => 'Valor deve ser um booleano.'];
                }
                break;

            case 'email':
                if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                    return ['valido' => false, 'mensagem' => 'Email inválido.'];
                }
                break;

            case 'date':
                if (!self::validarFormatoData($valor, 'Y-m-d')) {
                    return ['valido' => false, 'mensagem' => 'Data deve estar no formato YYYY-MM-DD.'];
                }
                break;

            case 'time':
                if (!self::validarFormatoData($valor, 'H:i:s')) {
                    return ['valido' => false, 'mensagem' => 'Hora deve estar no formato HH:MM:SS.'];
                }
                break;

            case 'timestamp':
                if (!self::validarFormatoData($valor, 'Y-m-d H:i:s')) {
                    return ['valido' => false, 'mensagem' => 'Timestamp deve estar no formato YYYY-MM-DD HH:MM:SS.'];
                }
                break;

            case 'uuid':
                if (!self::validarUuid($valor)) {
                    return ['valido' => false, 'mensagem' => 'UUID v4 inválido.'];
                }
                break;

            case 'json':
                if (!self::validarJson($valor)) {
                    return ['valido' => false, 'mensagem' => 'JSON inválido.'];
                }
                break;

            case 'array':
                if (!is_array($valor)) {
                    return ['valido' => false, 'mensagem' => 'Valor deve ser um array.'];
                }
                break;
        }

        return ['valido' => true, 'mensagem' => null];
    }

    // ========================================================================
    // VALIDAÇÕES CUSTOMIZADAS
    // ========================================================================

    /**
     * Validar padrão regex (pattern)
     */
    public static function validarpattern($valor, $pattern): array
    {
        if (!is_string($pattern)) {
            return ['valido' => false, 'mensagem' => 'Padrão regex inválido.'];
        }

        if (!preg_match($pattern, $valor)) {
            return ['valido' => false, 'mensagem' => "Valor não corresponde ao padrão: {$pattern}"];
        }

        return ['valido' => true, 'mensagem' => null];
    }

    /**
     * Validar comprimento mínimo (min_length)
     */
    public static function validarminlength($valor, $minimo): array
    {
        $minimo = (int) $minimo;
        $comprimento = strlen((string) $valor);

        if ($comprimento < $minimo) {
            return ['valido' => false, 'mensagem' => "Comprimento mínimo é {$minimo} caracteres (atual: {$comprimento})."];
        }

        return ['valido' => true, 'mensagem' => null];
    }

    /**
     * Validar comprimento máximo (max_length)
     */
    public static function validarmaxlength($valor, $maximo): array
    {
        $maximo = (int) $maximo;
        $comprimento = strlen((string) $valor);

        if ($comprimento > $maximo) {
            return ['valido' => false, 'mensagem' => "Comprimento máximo é {$maximo} caracteres (atual: {$comprimento})."];
        }

        return ['valido' => true, 'mensagem' => null];
    }

    /**
     * Validar valor mínimo (min)
     */
    public static function validarmin($valor, $minimo): array
    {
        $minimo = (int) $minimo;
        $valor = (int) $valor;

        if ($valor < $minimo) {
            return ['valido' => false, 'mensagem' => "Valor mínimo é {$minimo} (atual: {$valor})."];
        }

        return ['valido' => true, 'mensagem' => null];
    }

    /**
     * Validar valor máximo (max)
     */
    public static function validarmax($valor, $maximo): array
    {
        $maximo = (int) $maximo;
        $valor = (int) $valor;

        if ($valor > $maximo) {
            return ['valido' => false, 'mensagem' => "Valor máximo é {$maximo} (atual: {$valor})."];
        }

        return ['valido' => true, 'mensagem' => null];
    }

    /**
     * Validar email @udlog obrigatoriamente
     */
    public static function validarudlogonly($valor, $obrigatorio): array
    {
        if ($obrigatorio === false || $obrigatorio === 0) {
            return ['valido' => true, 'mensagem' => null];
        }

        if (!preg_match('/@udlog\.com$/i', $valor)) {
            return ['valido' => false, 'mensagem' => 'Email deve ser @udlog.com'];
        }

        return ['valido' => true, 'mensagem' => null];
    }

    /**
     * Validar valores permitidos (enum)
     */
    public static function validarenum($valor, array $valoresPermitidos): array
    {
        if (!in_array($valor, $valoresPermitidos, true)) {
            $opcoes = implode(', ', $valoresPermitidos);
            return ['valido' => false, 'mensagem' => "Valor deve ser um dos: {$opcoes}"];
        }

        return ['valido' => true, 'mensagem' => null];
    }

    /**
     * Validar que não está vazio (required)
     */
    public static function validarrequired($valor, $obrigatorio): array
    {
        if ($obrigatorio === false || $obrigatorio === 0) {
            return ['valido' => true, 'mensagem' => null];
        }

        if (empty($valor) && $valor !== '0' && $valor !== 0) {
            return ['valido' => false, 'mensagem' => 'Campo é obrigatório.'];
        }

        return ['valido' => true, 'mensagem' => null];
    }

    /**
     * Validar URL
     */
    public static function validarurl($valor, $validar = true): array
    {
        if ($validar === false || $validar === 0) {
            return ['valido' => true, 'mensagem' => null];
        }

        if (!filter_var($valor, FILTER_VALIDATE_URL)) {
            return ['valido' => false, 'mensagem' => 'URL inválida.'];
        }

        return ['valido' => true, 'mensagem' => null];
    }

    /**
     * Validar IP
     */
    public static function validarip($valor, $versao = null): array
    {
        $flags = 0;
        if ($versao === 4) {
            $flags = FILTER_FLAG_IPV4;
        } elseif ($versao === 6) {
            $flags = FILTER_FLAG_IPV6;
        }

        if (!filter_var($valor, FILTER_VALIDATE_IP, $flags)) {
            return ['valido' => false, 'mensagem' => 'IP inválido.'];
        }

        return ['valido' => true, 'mensagem' => null];
    }

    /**
     * Validar que é um número
     */
    public static function validarisnumeric($valor, $exigir = true): array
    {
        if ($exigir === false || $exigir === 0) {
            return ['valido' => true, 'mensagem' => null];
        }

        if (!is_numeric($valor)) {
            return ['valido' => false, 'mensagem' => 'Valor deve ser numérico.'];
        }

        return ['valido' => true, 'mensagem' => null];
    }

    // ========================================================================
    // HELPERS DE VALIDAÇÃO
    // ========================================================================

    /**
     * Validar formato de data
     */
    private static function validarFormatoData($valor, string $formato): bool
    {
        if (!is_string($valor)) {
            return false;
        }

        $date = \DateTime::createFromFormat($formato, $valor);
        return $date !== false && $date->format($formato) === $valor;
    }

    /**
     * Validar UUID v4
     */
    private static function validarUuid($valor): bool
    {
        if (!is_string($valor)) {
            return false;
        }

        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $valor
        );
    }

    /**
     * Validar JSON
     */
    private static function validarJson($valor): bool
    {
        if (is_string($valor)) {
            json_decode($valor);
            return json_last_error() === JSON_ERROR_NONE;
        }

        return false;
    }

    // ========================================================================
    // VALIDAÇÃO DE PAYLOAD COMPLETO
    // ========================================================================

    /**
     * Validar payload completo contra campos obrigatórios
     */
    public static function validarPayloadCompleto(array $payload, array $camposObrigatorios = []): array
    {
        $erros = [];

        foreach ($camposObrigatorios as $campo) {
            if (!isset($payload[$campo]) || $payload[$campo] === null) {
                $erros[] = "Campo obrigatório '{$campo}' não foi fornecido no payload.";
            }
        }

        return [
            'valido' => empty($erros),
            'erros' => $erros,
        ];
    }

    /**
     * Sanitizar valores para segurança
     */
    public static function sanitizar(array $payload): array
    {
        $sanitizado = [];

        foreach ($payload as $chave => $valor) {
            if (is_string($valor)) {
                $sanitizado[$chave] = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
            } else {
                $sanitizado[$chave] = $valor;
            }
        }

        return $sanitizado;
    }

    /**
     * Filtrar payload para remover campos não esperados
     */
    public static function filtrar(array $payload, array $camposEsperados): array
    {
        $filtrado = [];

        foreach ($camposEsperados as $campo) {
            if (isset($payload[$campo])) {
                $filtrado[$campo] = $payload[$campo];
            }
        }

        return $filtrado;
    }

    /**
     * Validar payload inteiro contra as regras de uma automação
     * 
     * @param array $payload Payload a validar
     * @param int $automacaoId ID da automação (para buscar regras)
     * 
     * @return array Lista de erros (vazio = válido)
     */
    public static function validar(array $payload, int $automacaoId): array
    {
        $erros = [];

        // Para cada campo no payload, validar contra regras
        foreach ($payload as $chave => $valor) {
            // Tipos básicos de validação
            if ($chave === 'execucaoId' && !is_int($valor)) {
                $erros[] = "execucaoId deve ser um inteiro";
            }
            if ($chave === 'clienteId' && !is_int($valor)) {
                $erros[] = "clienteId deve ser um inteiro";
            }
            if ($chave === 'emailDestino' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                $erros[] = "emailDestino deve ser um email válido";
            }
            if ($chave === 'modo' && !in_array($valor, ['AUTOMATICO', 'MANUAL'])) {
                $erros[] = "modo deve ser AUTOMATICO ou MANUAL";
            }
        }

        return $erros;
    }
}