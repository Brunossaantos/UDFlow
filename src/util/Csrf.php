<?php

namespace Udflow\util;

/**
 * Csrf
 *
 * Todo formulário que muda alguma coisa no sistema (POST) precisa
 * carregar um token gerado aqui. Sem o token certo, a requisição é
 * recusada antes de chegar em qualquer Controller.
 */
class Csrf
{
    private const CHAVE_SESSAO = 'csrf_token';

    public static function gerarToken(): string
    {
        if (empty($_SESSION[self::CHAVE_SESSAO])) {
            $_SESSION[self::CHAVE_SESSAO] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CHAVE_SESSAO];
    }

    /**
     * Compara o token que veio do formulário com o que está na sessão.
     * Usa hash_equals pra não dar brecha de timing attack.
     */
    public static function validarToken(?string $tokenRecebido): bool
    {
        if (empty($_SESSION[self::CHAVE_SESSAO]) || empty($tokenRecebido)) {
            return false;
        }

        return hash_equals($_SESSION[self::CHAVE_SESSAO], $tokenRecebido);
    }

    /** Gera o <input hidden> pronto pra colar dentro do <form> */
    public static function campoHtml(): string
    {
        $token = htmlspecialchars(self::gerarToken(), ENT_QUOTES, 'UTF-8');
        return "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
    }
}
