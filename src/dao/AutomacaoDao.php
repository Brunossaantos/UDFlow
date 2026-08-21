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

    /**
     * Busca uma automação ativa pelo "slug" da URL (ex: "kpi",
     * "status-ar" - sem o prefixo /automacoes/ e sem sufixo de ação).
     * É assim que o index.php resolve automações cadastradas
     * dinamicamente pela tela de Automações, sem precisar de uma
     * entrada fixa em config/rotas.php pra cada uma.
     */
    public function buscarPorRota(string $rotaBase): ?array
    {
        $sql = 'SELECT * FROM tb_automacoes WHERE rota = :rota AND ativo = 1 LIMIT 1';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':rota', '/automacoes/' . $rotaBase, PDO::PARAM_STR);
        $consulta->execute();

        $linha = $consulta->fetch();

        return $linha ?: null;
    }

    public function listarTodas(): array
    {
        $sql = 'SELECT * FROM tb_automacoes ORDER BY posicao';

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
        ?string $iconSvg = null,
        string $webhookUrl = '',
        string $webhookMetodo = 'POST',
        int $posicao = 1,
        bool $possuiAgendamento = false,
        bool $visivelParaUsuarios = true
    ): int {
        $sql = 'INSERT INTO tb_automacoes (nome, chave, rota, icon_svg, webhook_url, webhook_metodo, posicao, possui_agendamento, visivel_para_usuarios, ativo, criado_em)
                VALUES (:nome, :chave, :rota, :icon_svg, :webhook_url, :webhook_metodo, :posicao, :possui_agendamento, :visivel, 1, NOW())';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':nome', $nome, PDO::PARAM_STR);
        $comando->bindValue(':chave', $chave, PDO::PARAM_STR);
        $comando->bindValue(':rota', $rota, PDO::PARAM_STR);
        $comando->bindValue(':icon_svg', $iconSvg, $iconSvg === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
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
        ?string $iconSvg = null,
        string $webhookUrl = '',
        string $webhookMetodo = 'POST',
        int $posicao = 1,
        bool $possuiAgendamento = false,
        bool $visivelParaUsuarios = true,
        bool $ativo = true
    ): void {
        $sql = 'UPDATE tb_automacoes SET nome = :nome, icon_svg = :icon_svg, webhook_url = :webhook_url, webhook_metodo = :webhook_metodo,
                posicao = :posicao, possui_agendamento = :possui_agendamento, visivel_para_usuarios = :visivel,
                ativo = :ativo, atualizado_em = NOW() WHERE id = :id';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':nome', $nome, PDO::PARAM_STR);
        $comando->bindValue(':icon_svg', $iconSvg, $iconSvg === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->bindValue(':webhook_url', $webhookUrl, PDO::PARAM_STR);
        $comando->bindValue(':webhook_metodo', $webhookMetodo, PDO::PARAM_STR);
        $comando->bindValue(':posicao', $posicao, PDO::PARAM_INT);
        $comando->bindValue(':possui_agendamento', $possuiAgendamento ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':visivel', $visivelParaUsuarios ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':ativo', $ativo ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':id', $id, PDO::PARAM_INT);
        $comando->execute();
    }

    public function atualizarWebhook(int $id, string $webhookUrl, string $webhookMetodo): void
    {
        $sql = 'UPDATE tb_automacoes SET webhook_url = :webhook_url, webhook_metodo = :webhook_metodo, 
                atualizado_em = NOW() WHERE id = :id';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':webhook_url', $webhookUrl, PDO::PARAM_STR);
        $comando->bindValue(':webhook_metodo', $webhookMetodo, PDO::PARAM_STR);
        $comando->bindValue(':id', $id, PDO::PARAM_INT);
        $comando->execute();
    }
}