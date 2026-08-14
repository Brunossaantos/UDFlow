<?php

namespace Udflow\dao;

use Udflow\config\Conexao;
use PDO;

class LogAdminDao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::pegar();
    }

    /**
     * usuario_id aceita null pra ações do "sistema" (sem uma pessoa
     * logada por trás - ex: o primeiro usuário cadastrado, antes de
     * existir qualquer admin pra ser o "executor"). Qualquer valor
     * <= 0 é tratado como null também, de propósito: evita que um
     * 0 (ausência de sessão, por exemplo) tente gravar como se
     * fosse um id de usuário de verdade e quebre a chave
     * estrangeira com tb_usuarios.
     */
    public function registrar(?int $usuarioId, string $acao, ?string $entidade, ?int $entidadeId, ?string $detalhes): void
    {
        if ($usuarioId !== null && $usuarioId <= 0) {
            $usuarioId = null;
        }

        $sql = 'INSERT INTO tb_logs_admin (usuario_id, acao, entidade, entidade_id, detalhes)
                VALUES (:usuario_id, :acao, :entidade, :entidade_id, :detalhes)';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':usuario_id', $usuarioId, $usuarioId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $comando->bindValue(':acao', $acao, PDO::PARAM_STR);
        $comando->bindValue(':entidade', $entidade, $entidade === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->bindValue(':entidade_id', $entidadeId, $entidadeId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $comando->bindValue(':detalhes', $detalhes, $detalhes === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->execute();
    }

    public function listarRecentes(int $limite = 100): array
    {
        $sql = 'SELECT l.*, u.nome AS usuario_nome
                FROM tb_logs_admin l
                LEFT JOIN tb_usuarios u ON u.id = l.usuario_id
                ORDER BY l.criado_em DESC
                LIMIT :limite';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll();
    }
}
