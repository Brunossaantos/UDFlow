<?php

namespace Udflow\rn;

use Udflow\dao\ClienteDao;
use Udflow\dao\ExecucaoDao;
use Udflow\dao\AutomacaoDao;

/**
 * ExecucaoRn
 *
 * Regra comum às 3 automações: cliente + e-mail -> dispara o
 * webhook do n8n e guarda o registro da execução. O que muda de
 * uma automação pra outra é só a validação extra do e-mail (o KPI
 * hoje só aceita @udlog, as outras duas ainda não) - por isso essa
 * checagem fica num método que pode ser sobrescrito, em vez de um
 * "if" espalhado pelo Controller.
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

    public function buscarClientesParaAutocomplete(string $termo, string $automacaoChave): array
    {
        $termo = trim($termo);
        if ($termo === '') {
            return [];
        }

        return $this->clienteDao->buscarPorNomeEAutomacao($termo, $automacaoChave);
    }

    /**
     * @return array{sucesso: bool, mensagem: ?string}
     */
    public function executarManual(string $automacaoChave, int $clienteId, string $emailDestino, int $usuarioId): array
    {
        $erroEmail = $this->validarEmailDestino($emailDestino);
        if ($erroEmail !== null) {
            return ['sucesso' => false, 'mensagem' => $erroEmail];
        }

        $automacao = $this->automacaoDao->buscarPorChave($automacaoChave);
        if ($automacao === null) {
            return ['sucesso' => false, 'mensagem' => 'Automação não encontrada.'];
        }

        // sem webhook cadastrado não tem pra onde disparar - erro de
        // configuração, não de execução, então nem chega a criar
        // registro em tb_execucoes pra isso
        if (empty($automacao['webhook_url'])) {
            return ['sucesso' => false, 'mensagem' => 'Essa automação ainda não tem webhook configurado. Fala com o administrador.'];
        }

        $cliente = $this->clienteDao->buscarPorId($clienteId);
        if ($cliente === null || !$cliente->ativo) {
            return ['sucesso' => false, 'mensagem' => 'Cliente inválido.'];
        }

        $execucaoId = $this->execucaoDao->criar(
            (int) $automacao['id'],
            $clienteId,
            $usuarioId,
            'manual',
            $emailDestino
        );

        $configKpi = $this->clienteDao->buscarKpiConfig($clienteId);

        $logoUrl = trim((string) ($configKpi['logo_url'] ?? ''));

        $n8nRn = new N8nRn();
        $enviado = $n8nRn->dispararWebhook($automacao['webhook_url'], [
            'execucaoId' => $execucaoId,
            'clienteCodigo' => $cliente->codigoCliente,
            'clienteNome' => $cliente->nomeExibicao,
            'unidadeCodigo' => $cliente->unidadeNome,
            'emailDestino' => $emailDestino,
            'logoUrl' => $logoUrl,
            'modo' => 'MANUAL',
        ]);

        if (!$enviado) {
            $this->execucaoDao->atualizarStatus($execucaoId, 'erro', 'Não foi possível acionar o n8n.');
            return ['sucesso' => false, 'mensagem' => 'Não conseguimos disparar a automação agora. Tenta de novo em instantes.'];
        }

        return ['sucesso' => true, 'mensagem' => null];
    }

    /** Retorna null se o e-mail for válido, ou a mensagem de erro se não for */
    protected function validarEmailDestino(string $email): ?string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Informe um e-mail válido.';
        }

        return null;
    }
}
