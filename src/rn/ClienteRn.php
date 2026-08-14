<?php

namespace Udflow\rn;

use Udflow\dao\ClienteDao;
use Udflow\dao\AutomacaoDao;
use Udflow\dao\LogAdminDao;

/**
 * ClienteRn
 *
 * Cadastro único usado pelas 3 automações. Cor e logo (KPI) ficam
 * guardadas à parte, então só grava a config de KPI se o formulário
 * mandou algum desses campos preenchido.
 */
class ClienteRn
{
    private ClienteDao $clienteDao;
    private AutomacaoDao $automacaoDao;
    private LogAdminDao $logAdminDao;

    public function __construct()
    {
        $this->clienteDao = new ClienteDao();
        $this->automacaoDao = new AutomacaoDao();
        $this->logAdminDao = new LogAdminDao();
    }

    /**
     * Só confere se tem 14 dígitos depois de tirar ponto/traço/barra.
     * Não valida dígito verificador - se um dia precisar disso de
     * verdade (por exemplo, pra bater com a Receita), dá pra trocar
     * só essa função sem mexer em mais nada. Estático porque é só
     * transformação de texto, não toca em banco.
     */
    public static function normalizarCnpj(string $cnpj): ?string
    {
        $somenteDigitos = preg_replace('/\D/', '', $cnpj);

        return (is_string($somenteDigitos) && strlen($somenteDigitos) === 14) ? $somenteDigitos : null;
    }

    /**
     * @param array<int,bool> $automacoesAtivas automacao_id => true/false
     * @return array{sucesso: bool, mensagem: ?string, clienteId: ?int}
     */
    public function criar(array $dados, array $automacoesAtivas, int $executorId): array
    {
        $erro = $this->validarCampos($dados);
        if ($erro !== null) {
            return ['sucesso' => false, 'mensagem' => $erro, 'clienteId' => null];
        }

        $cnpj = self::normalizarCnpj($dados['cnpj']);
        if ($cnpj === null) {
            return ['sucesso' => false, 'mensagem' => 'CNPJ precisa ter 14 dígitos.', 'clienteId' => null];
        }

        if ($this->clienteDao->existeCodigoOuCnpj($dados['codigo_cliente'], $cnpj)) {
            return ['sucesso' => false, 'mensagem' => 'Já existe cliente com esse código ou CNPJ.', 'clienteId' => null];
        }

        $clienteId = $this->clienteDao->criar(
            (int) $dados['unidade_id'],
            $dados['codigo_cliente'],
            $dados['razao_social'],
            $dados['nome_exibicao'],
            $cnpj,
            $dados['email_responsavel'] ?: null
        );

        $this->salvarKpiConfigSeInformado($clienteId, $dados);
        $this->salvarAutomacoes($clienteId, $automacoesAtivas);

        $this->logAdminDao->registrar($executorId, 'cliente.criado', 'tb_clientes', $clienteId, "Cliente {$dados['nome_exibicao']} criado");

        return ['sucesso' => true, 'mensagem' => null, 'clienteId' => $clienteId];
    }

    public function atualizar(int $id, array $dados, array $automacoesAtivas, int $executorId): array
    {
        $erro = $this->validarCampos($dados);
        if ($erro !== null) {
            return ['sucesso' => false, 'mensagem' => $erro];
        }

        $cnpj = self::normalizarCnpj($dados['cnpj']);
        if ($cnpj === null) {
            return ['sucesso' => false, 'mensagem' => 'CNPJ precisa ter 14 dígitos.'];
        }

        if ($this->clienteDao->existeCodigoOuCnpj($dados['codigo_cliente'], $cnpj, $id)) {
            return ['sucesso' => false, 'mensagem' => 'Já existe outro cliente com esse código ou CNPJ.'];
        }

        $this->clienteDao->atualizar(
            $id,
            (int) $dados['unidade_id'],
            $dados['codigo_cliente'],
            $dados['razao_social'],
            $dados['nome_exibicao'],
            $cnpj,
            $dados['email_responsavel'] ?: null,
            !empty($dados['ativo'])
        );

        $this->salvarKpiConfigSeInformado($id, $dados);
        $this->salvarAutomacoes($id, $automacoesAtivas);

        $this->logAdminDao->registrar($executorId, 'cliente.atualizado', 'tb_clientes', $id, null);

        return ['sucesso' => true, 'mensagem' => null];
    }

    public function listar(?int $unidadeId = null): array
    {
        return $this->clienteDao->listarTodos($unidadeId);
    }

    private function validarCampos(array $dados): ?string
    {
        $obrigatorios = ['unidade_id', 'codigo_cliente', 'razao_social', 'nome_exibicao', 'cnpj'];
        foreach ($obrigatorios as $campo) {
            if (empty($dados[$campo])) {
                return 'Preenche todos os campos obrigatórios.';
            }
        }

        if (!empty($dados['email_responsavel']) && !filter_var($dados['email_responsavel'], FILTER_VALIDATE_EMAIL)) {
            return 'E-mail do responsável inválido.';
        }

        return null;
    }

    private function salvarKpiConfigSeInformado(int $clienteId, array $dados): void
    {
        $logoUrl = $dados['logo_url'] ?? null;
        $corPrimaria = $dados['cor_primaria'] ?? null;
        $corSecundaria = $dados['cor_secundaria'] ?? null;

        if ($logoUrl || $corPrimaria || $corSecundaria) {
            $this->clienteDao->salvarKpiConfig($clienteId, $logoUrl ?: null, $corPrimaria ?: null, $corSecundaria ?: null);
        }
    }

    private function salvarAutomacoes(int $clienteId, array $automacoesAtivas): void
    {
        foreach ($this->automacaoDao->listarTodas() as $automacao) {
            $ativo = !empty($automacoesAtivas[$automacao['id']]);
            $this->clienteDao->definirAutomacao($clienteId, (int) $automacao['id'], $ativo);
        }
    }
}
