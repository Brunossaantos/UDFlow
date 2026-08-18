<?php

namespace Udflow\rn;

use Udflow\dao\ClienteDao;
use Udflow\dao\ExecucaoDao;
use Udflow\dao\AutomacaoDao;

/**
 * Regra comum para execução das automações.
 *
 * Recebe cliente e e-mail, registra a execução e dispara
 * o webhook configurado para a automação no n8n.
 */
class ExecucaoRn
{
    protected ClienteDao $clienteDao;
    protected ExecucaoDao $execucaoDao;
    protected AutomacaoDao $automacaoDao;

    public function __construct()
    {
        $this->clienteDao = new ClienteDao();
        $this->execucaoDao = new ExecucaoDao();
        $this->automacaoDao = new AutomacaoDao();
    }

    public function buscarClientesParaAutocomplete(
        string $termo,
        string $automacaoChave
    ): array {
        $termo = trim($termo);

        if ($termo === '') {
            return [];
        }

        return $this->clienteDao->buscarPorNomeEAutomacao(
            $termo,
            $automacaoChave
        );
    }

    /**
     * Executa uma automação manualmente.
     *
     * @return array{sucesso: bool, mensagem: ?string}
     */
    public function executarManual(
        string $automacaoChave,
        int $clienteId,
        string $emailDestino,
        int $usuarioId
    ): array {
        return $this->executar(
            $automacaoChave,
            $clienteId,
            $emailDestino,
            'manual',
            $usuarioId
        );
    }

    /**
     * Executa uma automação automaticamente pelo cron.
     *
     * @return array{sucesso: bool, mensagem: ?string}
     */
    public function executarAutomatico(
        string $automacaoChave,
        int $clienteId,
        string $emailDestino
    ): array {
        return $this->executar(
            $automacaoChave,
            $clienteId,
            $emailDestino,
            'automatico',
            null
        );
    }

    /**
     * Executa a automação e dispara o webhook do n8n.
     *
     * @return array{sucesso: bool, mensagem: ?string}
     */
    private function executar(
        string $automacaoChave,
        int $clienteId,
        string $emailDestino,
        string $origem,
        ?int $usuarioId
    ): array {
        /*
         * Valida o e-mail informado.
         */
        $erroEmail = $this->validarEmailDestino($emailDestino);

        if ($erroEmail !== null) {
            return [
                'sucesso' => false,
                'mensagem' => $erroEmail,
            ];
        }

        /*
         * Busca a automação pela chave.
         */
        $automacao = $this->automacaoDao->buscarPorChave(
            $automacaoChave
        );

        if ($automacao === null) {
            return [
                'sucesso' => false,
                'mensagem' => 'Automação não encontrada.',
            ];
        }

        /*
         * Verifica se existe webhook configurado.
         */
        if (empty($automacao['webhook_url'])) {
            return [
                'sucesso' => false,
                'mensagem' => 'Essa automação ainda não tem webhook configurado. Fala com o administrador.',
            ];
        }

        /*
         * Busca o cliente.
         */
        $cliente = $this->clienteDao->buscarPorId($clienteId);

        if ($cliente === null || !$cliente->ativo) {
            return [
                'sucesso' => false,
                'mensagem' => 'Cliente inválido.',
            ];
        }

        /*
         * Automações que precisam receber o código Talent.
         *
         * Futuramente, basta adicionar uma nova chave neste array.
         */
        $automacoesComCodigoTalent = [
            'relatorio_avarias',
        ];

        $precisaCodigoTalent = in_array(
            $automacaoChave,
            $automacoesComCodigoTalent,
            true
        );

        /*
         * Valida o código Talent somente quando necessário.
         */
        if (
            $precisaCodigoTalent
            && (
                $cliente->codigoTalent === null
                || trim($cliente->codigoTalent) === ''
            )
        ) {
            return [
                'sucesso' => false,
                'mensagem' => 'Esse cliente ainda não tem o código do Talent cadastrado. Cadastre em Administração > Clientes antes de disparar o Relatório de Avarias.',
            ];
        }

        /*
         * Para a Estadia, a razão social é necessária para
         * localizar o depositante retornado pela API Talent.
         */
        if (
            $automacaoChave === 'estadia'
            && (
                $cliente->razaoSocial === null
                || trim($cliente->razaoSocial) === ''
            )
        ) {
            return [
                'sucesso' => false,
                'mensagem' => 'Esse cliente ainda não possui razão social cadastrada.',
            ];
        }

        /*
         * Registra a execução antes de chamar o n8n.
         */
        $execucaoId = $this->execucaoDao->criar(
            (int) $automacao['id'],
            $clienteId,
            $usuarioId,
            $origem,
            $emailDestino
        );

        /*
         * Payload comum para todas as automações.
         */
        $payload = [
            'execucaoId' => $execucaoId,
            'clienteCodigo' => $cliente->codigoCliente,
            'clienteNome' => $cliente->nomeExibicao,
            'unidadeCodigo' => $cliente->unidadeNome,
            'emailDestino' => $emailDestino,
            'modo' => $origem === 'automatico'
                ? 'AUTOMATICO'
                : 'MANUAL',
        ];

        /*
         * A razão social será enviada somente para a Estadia.
         */
        if ($automacaoChave === 'estadia') {
            $payload['clienteRazaoSocial'] =
                $cliente->razaoSocial;
        }

        /*
         * O código Talent será enviado somente para
         * as automações configuradas anteriormente.
         */
        if ($precisaCodigoTalent) {
            $payload['codigoTalent'] =
                $cliente->codigoTalent;
        }

        /*
         * Dispara o webhook do n8n.
         */
        $n8nRn = new N8nRn();

        $enviado = $n8nRn->dispararWebhook(
            $automacao['webhook_url'],
            $payload
        );

        /*
         * Atualiza a execução caso o disparo falhe.
         */
        if (!$enviado) {
            $this->execucaoDao->atualizarStatus(
                $execucaoId,
                'erro',
                'Não foi possível acionar o n8n.'
            );

            return [
                'sucesso' => false,
                'mensagem' => 'Não conseguimos disparar a automação agora. Tenta de novo em instantes.',
            ];
        }

        return [
            'sucesso' => true,
            'mensagem' => null,
        ];
    }

    /**
     * Retorna null se o e-mail for válido.
     * Caso contrário, retorna a mensagem de erro.
     */
    protected function validarEmailDestino(
        string $email
    ): ?string {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Informe um e-mail válido.';
        }

        return null;
    }
}