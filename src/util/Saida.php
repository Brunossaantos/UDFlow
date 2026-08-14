<?php

namespace Udflow\util;

/**
 * Saida
 *
 * Uma função só, mas que evita um monte de dor de cabeça: toda vez
 * que a gente for imprimir alguma coisa que veio do banco ou do
 * usuário dentro de uma view, passa por aqui. Sem isso, um cliente
 * cadastrado com um nome tipo <script>...</script> vira um ataque
 * de XSS na tela de qualquer um que abrir a lista de clientes.
 *
 * Uso na view: <?= Saida::e($cliente['nome_exibicao']) ?>
 */
class Saida
{
    public static function e(?string $valor): string
    {
        return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
    }

    /** Responde em JSON e encerra a requisição - usado nas ações via fetch/AJAX */
    public static function json(array $dados, int $codigoHttp = 200)
    {
        http_response_code($codigoHttp);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
