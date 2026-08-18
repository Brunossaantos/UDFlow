<?php

namespace Udflow\dao;

use Udflow\config\Conexao;
use PDO;

class CronogramaDao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::pegar();
    }

    /**
     * Lista completa pra tela de admin - diferente da view
     * vw_cronograma_ativo, que só mostra os ativos. Aqui a gente
     * precisa ver os pausados também, pra poder reativar.
     */
    public function listar(array $filtros = []): array
    {
        $condicoes = ['1 = 1'];
        $parametros = [];

        if (!empty($filtros['automacao_id'])) {
            $condicoes[] = 'cr.automacao_id = :automacao_id';
            $parametros[':automacao_id'] = $filtros['automacao_id'];
        }
        if (!empty($filtros['unidade_id'])) {
            $condicoes[] = 'c.unidade_id = :unidade_id';
            $parametros[':unidade_id'] = $filtros['unidade_id'];
        }

        $sql = 'SELECT cr.*, a.nome AS automacao_nome, a.chave AS automacao_chave,
                       c.nome_exibicao AS cliente_nome, u.nome AS unidade_nome
                FROM tb_cronograma cr
                JOIN tb_automacoes a ON a.id = cr.automacao_id
                JOIN tb_clientes c ON c.id = cr.cliente_id
                JOIN tb_unidades u ON u.id = c.unidade_id
                WHERE ' . implode(' AND ', $condicoes) . '
                ORDER BY cr.dia_mes, cr.horario';

        $consulta = $this->pdo->prepare($sql);
        foreach ($parametros as $chave => $valor) {
            $consulta->bindValue($chave, $valor, PDO::PARAM_INT);
        }
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT cr.*, a.chave AS automacao_chave, c.email_responsavel, c.nome_exibicao AS cliente_nome
                FROM tb_cronograma cr
                JOIN tb_automacoes a ON a.id = cr.automacao_id
                JOIN tb_clientes c ON c.id = cr.cliente_id
                WHERE cr.id = :id
                LIMIT 1';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        $linha = $consulta->fetch();

        return $linha ?: null;
    }

    public function definirAtivo(int $id, bool $ativo): void
    {
        $sql = 'UPDATE tb_cronograma SET ativo = :ativo WHERE id = :id';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':ativo', $ativo ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':id', $id, PDO::PARAM_INT);
        $comando->execute();
    }

    public function criar(int $automacaoId, int $clienteId, string $frequencia, ?string $diasSemana, ?int $diaMes, string $horario): int
    {
        $sql = 'INSERT INTO tb_cronograma (automacao_id, cliente_id, frequencia, dias_semana, dia_mes, horario)
                VALUES (:automacao_id, :cliente_id, :frequencia, :dias_semana, :dia_mes, :horario)';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $comando->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
        $comando->bindValue(':frequencia', $frequencia, PDO::PARAM_STR);
        $comando->bindValue(':dias_semana', $diasSemana, $diasSemana === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->bindValue(':dia_mes', $diaMes, $diaMes === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $comando->bindValue(':horario', $horario, PDO::PARAM_STR);
        $comando->execute();

        return (int) $this->pdo->lastInsertId();
    }


    public function existeHorario(int $automacaoId, int $clienteId, string $horario): bool
    {
        $sql = 'SELECT COUNT(*) FROM tb_cronograma
                WHERE automacao_id = :automacao_id AND cliente_id = :cliente_id AND horario = :horario';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $consulta->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
        $consulta->bindValue(':horario', $horario, PDO::PARAM_STR);
        $consulta->execute();

        return ((int) $consulta->fetchColumn()) > 0;
    }

    /**
     * O que precisa disparar agora - chamado pelo
     * cron/executar_agendamentos.php a cada minuto. $horario no
     * formato "HH:MM:00", $diaMes é o dia do mês atual (só importa
     * pra frequencia='mensal' - itens 'diaria' rodam todo dia).
     */
    public function buscarParaExecutarAgora(string $horario, int $diaMes, int $diaSemana): array
    {
        $sql = "SELECT cr.*, a.chave AS automacao_chave, c.email_responsavel, c.nome_exibicao AS cliente_nome
                FROM tb_cronograma cr
                JOIN tb_automacoes a ON a.id = cr.automacao_id
                JOIN tb_clientes c ON c.id = cr.cliente_id
                WHERE cr.ativo = 1
                  AND cr.horario = :horario
                  AND (cr.frequencia = 'diaria' OR (cr.frequencia = 'mensal' AND cr.dia_mes = :dia_mes))
                  AND (cr.dias_semana IS NULL OR FIND_IN_SET(:dia_semana, cr.dias_semana) > 0)";

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':horario', $horario, PDO::PARAM_STR);
        $consulta->bindValue(':dia_mes', $diaMes, PDO::PARAM_INT);
        $consulta->bindValue(':dia_semana', $diaSemana, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll();
    }
}
