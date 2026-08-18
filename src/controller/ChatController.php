<?php

namespace Udflow\controller;

use Udflow\rn\ChatRn;
use Udflow\util\Csrf;
use Udflow\util\Saida;
use Udflow\util\ControleAcesso;

class ChatController
{
    private const CHAVE_SESSAO_HISTORICO = 'chat_historico';
    private const MAXIMO_MENSAGENS_HISTORICO = 12; // guarda só as últimas trocas, pra não deixar o prompt gigante

    public function enviar(): void
    {
        ControleAcesso::exigirLogin();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $mensagem = trim($_POST['mensagem'] ?? '');
        if ($mensagem === '') {
            Saida::json(['sucesso' => false, 'mensagem' => 'Escreve alguma coisa antes de enviar.'], 422);
        }

        $historico = $_SESSION[self::CHAVE_SESSAO_HISTORICO] ?? [];

        $resultado = (new ChatRn())->responder($historico, $mensagem);

        if (!$resultado['sucesso']) {
            Saida::json($resultado, 422);
        }

        $historico[] = ['role' => 'user', 'content' => $mensagem];
        $historico[] = ['role' => 'assistant', 'content' => $resultado['resposta']];

        // mantém só as últimas trocas, pra sessão não crescer sem limite
        if (count($historico) > self::MAXIMO_MENSAGENS_HISTORICO) {
            $historico = array_slice($historico, -self::MAXIMO_MENSAGENS_HISTORICO);
        }

        $_SESSION[self::CHAVE_SESSAO_HISTORICO] = $historico;

        Saida::json(['sucesso' => true, 'resposta' => $resultado['resposta']]);
    }

    public function limparHistorico(): void
    {
        ControleAcesso::exigirLogin();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        unset($_SESSION[self::CHAVE_SESSAO_HISTORICO]);

        Saida::json(['sucesso' => true]);
    }
}