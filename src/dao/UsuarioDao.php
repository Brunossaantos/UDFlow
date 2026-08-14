<?php

namespace Udflow\dao;

use Udflow\config\Conexao;
use Udflow\model\Usuario;
use PDO;

/**
 * UsuarioDao
 *
 * Único lugar do sistema que roda SQL em cima de tb_usuarios. Toda
 * consulta aqui usa parâmetro nomeado (:algumacoisa) e bindValue -
 * nunca, em hipótese nenhuma, concatena a entrada do usuário direto
 * na string do SQL. É assim que a gente evita SQL injection: o
 * MariaDB recebe a query e os valores separados, então não tem
 * como um "usuario' OR '1'='1" virar comando.
 */
class UsuarioDao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::pegar();
    }

    public function buscarPorLogin(string $usuario): ?Usuario
    {
        $sql = 'SELECT * FROM tb_usuarios WHERE usuario = :usuario AND ativo = 1 LIMIT 1';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':usuario', $usuario, PDO::PARAM_STR);
        $consulta->execute();

        $linha = $consulta->fetch();

        return $linha ? Usuario::apartirDoBanco($linha) : null;
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        $sql = 'SELECT * FROM tb_usuarios WHERE email = :email AND ativo = 1 LIMIT 1';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':email', $email, PDO::PARAM_STR);
        $consulta->execute();

        $linha = $consulta->fetch();

        return $linha ? Usuario::apartirDoBanco($linha) : null;
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $sql = 'SELECT * FROM tb_usuarios WHERE id = :id LIMIT 1';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        $linha = $consulta->fetch();

        return $linha ? Usuario::apartirDoBanco($linha) : null;
    }

    public function existeLoginOuEmail(string $usuario, string $email): bool
    {
        $sql = 'SELECT COUNT(*) FROM tb_usuarios WHERE usuario = :usuario OR email = :email';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':usuario', $usuario, PDO::PARAM_STR);
        $consulta->bindValue(':email', $email, PDO::PARAM_STR);
        $consulta->execute();

        return ((int) $consulta->fetchColumn()) > 0;
    }

    public function criar(string $nome, string $usuario, string $email, string $senhaHash, bool $superAdmin): int
    {
        $sql = 'INSERT INTO tb_usuarios (nome, usuario, email, senha_hash, super_admin, trocar_senha_no_login)
                VALUES (:nome, :usuario, :email, :senha_hash, :super_admin, 1)';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':nome', $nome, PDO::PARAM_STR);
        $comando->bindValue(':usuario', $usuario, PDO::PARAM_STR);
        $comando->bindValue(':email', $email, PDO::PARAM_STR);
        $comando->bindValue(':senha_hash', $senhaHash, PDO::PARAM_STR);
        $comando->bindValue(':super_admin', $superAdmin ? 1 : 0, PDO::PARAM_INT);
        $comando->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizarSenha(int $usuarioId, string $novaSenhaHash, bool $forcarTrocaNoProximoLogin = false): void
    {
        $sql = 'UPDATE tb_usuarios
                SET senha_hash = :senha_hash, trocar_senha_no_login = :trocar
                WHERE id = :id';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':senha_hash', $novaSenhaHash, PDO::PARAM_STR);
        $comando->bindValue(':trocar', $forcarTrocaNoProximoLogin ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':id', $usuarioId, PDO::PARAM_INT);
        $comando->execute();
    }

    public function definirAtivo(int $usuarioId, bool $ativo): void
    {
        $sql = 'UPDATE tb_usuarios SET ativo = :ativo WHERE id = :id';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':ativo', $ativo ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':id', $usuarioId, PDO::PARAM_INT);
        $comando->execute();
    }

    public function listarTodos(): array
    {
        $sql = 'SELECT * FROM tb_usuarios ORDER BY nome';

        $consulta = $this->pdo->query($sql);

        return array_map(fn ($linha) => Usuario::apartirDoBanco($linha), $consulta->fetchAll());
    }
}
