<?php

namespace Udflow\dao;

use Udflow\config\Conexao;
use PDO;

/**
 * PermissaoDao
 *
 * Fala só com a vw_permissoes_usuario. Essa view já resolve pra
 * gente a regra "se super_admin, o papel é admin em tudo" - aqui a
 * gente só busca a linha certa, sem duplicar aquela lógica em PHP.
 */
class PermissaoDao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::pegar();
    }

    /** Retorna 'admin', 'usuario' ou null (sem acesso nenhum) */
    public function papelDoUsuarioNaAutomacao(int $usuarioId, string $automacaoChave): ?string
    {
        $sql = 'SELECT papel_efetivo
                FROM vw_permissoes_usuario
                WHERE usuario_id = :usuario_id AND automacao_chave = :chave
                LIMIT 1';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $consulta->bindValue(':chave', $automacaoChave, PDO::PARAM_STR);
        $consulta->execute();

        $linha = $consulta->fetch();

        return $linha['papel_efetivo'] ?? null;
    }

    /** Automações que o usuário enxerga na sidebar, já na ordem certa */
    public function automacoesVisiveisParaUsuario(int $usuarioId): array
    {
        $sql = "SELECT a.chave, a.nome, a.icone, a.rota, p.papel_efetivo
                FROM vw_permissoes_usuario p
                JOIN tb_automacoes a ON a.id = p.automacao_id
                WHERE p.usuario_id = :usuario_id
                  AND p.papel_efetivo IS NOT NULL
                  AND (p.visivel_para_usuarios = 1 OR p.papel_efetivo = 'admin')
                ORDER BY a.ordem_menu";

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function definirPapel(int $usuarioId, int $automacaoId, string $papel): void
    {
        // insere ou já atualiza se a combinação usuario+automação já existir.
        // MariaDB com prepared statement de verdade (sem emulação) não deixa
        // reusar o mesmo parâmetro nomeado duas vezes na mesma query - por
        // isso :papel e :papel2, mesmo apontando pro mesmo valor.
        $sql = 'INSERT INTO tb_usuario_automacao (usuario_id, automacao_id, papel)
                VALUES (:usuario_id, :automacao_id, :papel)
                ON DUPLICATE KEY UPDATE papel = :papel2';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $comando->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $comando->bindValue(':papel', $papel, PDO::PARAM_STR);
        $comando->bindValue(':papel2', $papel, PDO::PARAM_STR);
        $comando->execute();
    }

    public function removerAcesso(int $usuarioId, int $automacaoId): void
    {
        $sql = 'DELETE FROM tb_usuario_automacao WHERE usuario_id = :usuario_id AND automacao_id = :automacao_id';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $comando->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $comando->execute();
    }

    /** Todas as automações + o papel do usuário em cada uma (null = sem acesso) - usado no modal de editar usuário */
    public function permissoesPorUsuario(int $usuarioId): array
    {
        $sql = 'SELECT a.id AS automacao_id, a.chave, a.nome, ua.papel
                FROM tb_automacoes a
                LEFT JOIN tb_usuario_automacao ua ON ua.automacao_id = a.id AND ua.usuario_id = :usuario_id
                WHERE a.ativo = 1
                ORDER BY a.ordem_menu';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    /** Usado pelo ControleAcesso pra liberar telas de admin "gerais" (Logs, Clientes, Cronograma) */
    public function usuarioEhAdminDeAlgumaAutomacao(int $usuarioId): bool
    {
        $sql = "SELECT COUNT(*) FROM tb_usuario_automacao WHERE usuario_id = :usuario_id AND papel = 'admin'";

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $consulta->execute();

        return ((int) $consulta->fetchColumn()) > 0;
    }
}
