<?php

namespace Udflow\dao;

use Udflow\config\Conexao;
use PDO;

class RedefinicaoSenhaDao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::pegar();
    }

    /**
     * $minutosValidade calculado aqui dentro, no NOW() do próprio
     * MariaDB - de propósito, pra nunca comparar um horário calculado
     * em PHP com o NOW() do banco. Se o fuso horário do PHP e do
     * MariaDB não estiverem exatamente sincronizados (bem comum:
     * servidor de banco em UTC, aplicação configurada em
     * America/Sao_Paulo), um código recém-gerado pode parecer
     * "expirado" na hora, porque os dois relógios divergem. Deixando
     * o MariaDB calcular a própria expiração, o relógio usado pra
     * escrever e pra comparar é sempre o mesmo.
     */
    public function criar(int $usuarioId, string $codigoHash, int $minutosValidade): int
    {
        $sql = 'INSERT INTO tb_password_resets (usuario_id, codigo_hash, expira_em)
                VALUES (:usuario_id, :codigo_hash, DATE_ADD(NOW(), INTERVAL :minutos MINUTE))';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $comando->bindValue(':codigo_hash', $codigoHash, PDO::PARAM_STR);
        $comando->bindValue(':minutos', $minutosValidade, PDO::PARAM_INT);
        $comando->execute();

        return (int) $this->pdo->lastInsertId();
    }

    /** Pega o pedido de redefinição mais recente e ainda não usado desse usuário */
    public function buscarValidoPorUsuario(int $usuarioId): ?array
    {
        $sql = 'SELECT * FROM tb_password_resets
                WHERE usuario_id = :usuario_id AND usado = 0 AND expira_em >= NOW()
                ORDER BY criado_em DESC
                LIMIT 1';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $consulta->execute();

        $linha = $consulta->fetch();

        return $linha ?: null;
    }

    public function marcarComoUsado(int $id): void
    {
        $sql = 'UPDATE tb_password_resets SET usado = 1 WHERE id = :id';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':id', $id, PDO::PARAM_INT);
        $comando->execute();
    }

    public function invalidarPendentesDoUsuario(int $usuarioId): void
    {
        // se a pessoa pedir um código novo, os anteriores não devem
        // continuar valendo - evita ficar com vários códigos ativos
        $sql = 'UPDATE tb_password_resets SET usado = 1 WHERE usuario_id = :usuario_id AND usado = 0';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $comando->execute();
    }
}
