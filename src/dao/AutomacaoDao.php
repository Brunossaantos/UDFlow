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
        string $nome,
        string $chave,
        string $rota,
        string $webhookUrl = '',
        string $webhookMetodo = 'POST',
        int $posicao = 1,
        bool $possuiAgendamento = false,
        bool $visivelParaUsuarios = true
    ): int {
        $sql = 'INSERT INTO tb_automacoes (
            nome,
            chave,
            rota,
            webhook_url,
            webhook_metodo,
            ordem_menu,
            possui_agendamento,
            visivel_para_usuarios,
            ativo,
            criado_em
        )
        VALUES (
            :nome,
            :chave,
            :rota,
            :webhook_url,
            :webhook_metodo,
            :posicao,
            :possui_agendamento,
            :visivel,
            1,
            NOW()
        )';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':nome', $nome, PDO::PARAM_STR);
        $comando->bindValue(':chave', $chave, PDO::PARAM_STR);
        $comando->bindValue(':rota', $rota, PDO::PARAM_STR);
        $comando->bindValue(':webhook_url', $webhookUrl, PDO::PARAM_STR);
        $comando->bindValue(':webhook_metodo', $webhookMetodo, PDO::PARAM_STR);
        $comando->bindValue(':posicao', $posicao, PDO::PARAM_INT);
        $comando->bindValue(':possui_agendamento', $possuiAgendamento ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':visivel', $visivelParaUsuarios ? 1 : 0, PDO::PARAM_INT);
        $comando->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(
        int $id,
        string $nome,
        string $webhookUrl = '',
        string $webhookMetodo = 'POST',
        int $posicao = 1,
        bool $possuiAgendamento = false,
        bool $visivelParaUsuarios = true,
        bool $ativo = true
    ): void {
        $sql = 'UPDATE tb_automacoes
        SET nome = :nome,
            webhook_url = :webhook_url,
            webhook_metodo = :webhook_metodo,
            ordem_menu = :posicao,
            possui_agendamento = :possui_agendamento,
            visivel_para_usuarios = :visivel,
            ativo = :ativo,
            atualizado_em = NOW()
        WHERE id = :id';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':nome', $nome, PDO::PARAM_STR);
        $comando->bindValue(':webhook_url', $webhookUrl, PDO::PARAM_STR);
        $comando->bindValue(':webhook_metodo', $webhookMetodo, PDO::PARAM_STR);
        $comando->bindValue(':posicao', $posicao, PDO::PARAM_INT);
        $comando->bindValue(':possui_agendamento', $possuiAgendamento ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':visivel', $visivelParaUsuarios ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':ativo', $ativo ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':id', $id, PDO::PARAM_INT);
        $comando->execute();
    }
}