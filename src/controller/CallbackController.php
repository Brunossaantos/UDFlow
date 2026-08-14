<?php

namespace Udflow\controller;

use Udflow\dao\ExecucaoDao;
use Udflow\util\Saida;

/**
 * CallbackController
 *
 * Endpoint que o próprio n8n chama no final do fluxo (sucesso ou
 * erro) pra avisar o UDFlow que pode atualizar o status. Não é uma
 * rota de usuário, então não passa pelo login - a segurança dela é
 * o token fixo no header, o mesmo que o UDFlow usa pra chamar o
 * n8n (N8nRn). Sem o token certo, a requisição nem chega a tocar
 * no banco.
 */
class CallbackController
{
    public function atualizarStatus(): void
    {
        $tokenRecebido = $_SERVER['HTTP_X_UDFLOW_TOKEN'] ?? '';

        if (!hash_equals($_ENV['N8N_WEBHOOK_TOKEN'], $tokenRecebido)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Token inválido.'], 401);
        }

        $corpo = json_decode(file_get_contents('php://input'), true) ?? [];

        $execucaoId = (int) ($corpo['execucaoId'] ?? 0);
        $status = $corpo['status'] ?? '';

        $statusValidos = ['pendente', 'processando', 'concluido', 'erro'];
        if ($execucaoId <= 0 || !in_array($status, $statusValidos, true)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Dados inválidos.'], 422);
        }

        (new ExecucaoDao())->atualizarStatus(
            $execucaoId,
            $status,
            $corpo['mensagemErro'] ?? null,
            $corpo['arquivoUrl'] ?? null
        );

        Saida::json(['sucesso' => true]);
    }
}
