<?php

namespace Udflow\dao;

use Udflow\config\Conexao;
use PDO;

class AutomacaoDao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::pegar();
    }

    public function buscarPorChave(string $chave): ?array
    {
        $sql = 'SELECT * FROM tb_automacoes WHERE chave = :chave AND ativo = 1 LIMIT 1';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':chave', $chave, PDO::PARAM_STR);
        $consulta->execute();

        $linha = $consulta->fetch();

        return $linha ?: null;
    }

    public function listarTodas(): array
    {
        $sql = 'SELECT * FROM tb_automacoes ORDER BY ordem_menu';

        return $this->pdo->query($sql)->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT * FROM tb_automacoes WHERE id = :id LIMIT 1';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        $linha = $consulta->fetch();

        return $linha ?: null;
    }

    public function atualizarVisibilidade(int $id, bool $visivelParaUsuarios): void
    {
        $sql = 'UPDATE tb_automacoes SET visivel_para_usuarios = :visivel WHERE id = :id';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':visivel', $visivelParaUsuarios ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':id', $id, PDO::PARAM_INT);
        $comando->execute();
    }

    public function criar(
        string $chave,
        string $nome,
        string $icone,
        string $rota,
        bool $permiteExecucaoManual,
        bool $possuiAgendamento,
        bool $visivelParaUsuarios,
        int $ordemMenu
    ): int {
        $sql = 'INSERT INTO tb_automacoes (chave, nome, icone, rota, permite_execucao_manual, possui_agendamento, visivel_para_usuarios, ordem_menu)
                VALUES (:chave, :nome, :icone, :rota, :permite_manual, :possui_agendamento, :visivel, :ordem)';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':chave', $chave, PDO::PARAM_STR);
        $comando->bindValue(':nome', $nome, PDO::PARAM_STR);
        $comando->bindValue(':icone', $icone, PDO::PARAM_STR);
        $comando->bindValue(':rota', $rota, PDO::PARAM_STR);
        $comando->bindValue(':permite_manual', $permiteExecucaoManual ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':possui_agendamento', $possuiAgendamento ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':visivel', $visivelParaUsuarios ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':ordem', $ordemMenu, PDO::PARAM_INT);
        $comando->execute();

        return (int) $this->pdo->lastInsertId();
    }
}
