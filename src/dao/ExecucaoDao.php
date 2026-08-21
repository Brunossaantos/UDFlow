<?php

namespace Udflow\dao;

use Udflow\config\Conexao;
use PDO;

class ExecucaoDao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::pegar();
    }

    public function criar(
        int $automacaoId,
        int $clienteId,
        ?int $usuarioId,
        string $origem,
        string $emailDestino
    ): int {
        $sql = 'INSERT INTO tb_execucoes (automacao_id, cliente_id, usuario_id, origem, email_destino, status)
                VALUES (:automacao_id, :cliente_id, :usuario_id, :origem, :email_destino, "pendente")';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $comando->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
        $comando->bindValue(':usuario_id', $usuarioId, $usuarioId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $comando->bindValue(':origem', $origem, PDO::PARAM_STR);
        $comando->bindValue(':email_destino', $emailDestino, PDO::PARAM_STR);
        $comando->execute();

        return (int) $this->pdo->lastInsertId();
    }

    /** Chamado pelo endpoint de callback que o n8n avisa quando termina */
    public function atualizarStatus(int $execucaoId, string $status, ?string $mensagemErro = null, ?string $arquivoUrl = null): void
    {
        $sql = 'UPDATE tb_execucoes
                SET status = :status,
                    mensagem_erro = :mensagem_erro,
                    arquivo_url = :arquivo_url,
                    finalizado_em = CASE WHEN :status2 IN ("concluido", "erro") THEN NOW() ELSE finalizado_em END
                WHERE id = :id';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':status', $status, PDO::PARAM_STR);
        $comando->bindValue(':status2', $status, PDO::PARAM_STR);
        $comando->bindValue(':mensagem_erro', $mensagemErro, $mensagemErro === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->bindValue(':arquivo_url', $arquivoUrl, $arquivoUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->bindValue(':id', $execucaoId, PDO::PARAM_INT);
        $comando->execute();
    }

    /** Alimenta a tabela "minhas solicitações" - só as do usuário logado */
    public function listarDoUsuario(int $usuarioId, string $automacaoChave, int $limite = 20): array
    {
        $this->expirarPendentesAntigas();

        $sql = 'SELECT e.*, c.nome_exibicao AS cliente_nome
                FROM tb_execucoes e
                JOIN tb_clientes c ON c.id = e.cliente_id
                JOIN tb_automacoes a ON a.id = e.automacao_id
                WHERE e.usuario_id = :usuario_id AND a.chave = :chave
                ORDER BY e.criado_em DESC
                LIMIT :limite';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $consulta->bindValue(':chave', $automacaoChave, PDO::PARAM_STR);
        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    /**
     * O n8n chama o callback quando termina - mas se cair, travar ou
     * nunca responder, a execução ficaria "pendente"/"processando"
     * pra sempre. Toda leitura da lista de execuções passa por aqui
     * primeiro e "expira" quem passou de 3 minutos sem resposta,
     * virando erro com uma mensagem clara pro usuário.
     */
    public function expirarPendentesAntigas(int $minutosLimite = 3): void
    {
        $sql = "UPDATE tb_execucoes
                SET status = 'erro',
                    mensagem_erro = 'Não foi possível gerar. O sistema demorou demais para responder.',
                    finalizado_em = NOW()
                WHERE status IN ('pendente', 'processando')
                  AND criado_em < DATE_SUB(NOW(), INTERVAL :minutos MINUTE)";

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':minutos', $minutosLimite, PDO::PARAM_INT);
        $comando->execute();
    }

    /** Alimenta a tela de admin "Logs e status" - todo mundo, com filtros opcionais */
    public function listarComFiltros(array $filtros = [], int $limite = 100): array
    {
        $condicoes = ['1 = 1'];
        $parametros = [];

        if (!empty($filtros['automacao_chave'])) {
            $condicoes[] = 'automacao_chave = :automacao_chave';
            $parametros[':automacao_chave'] = $filtros['automacao_chave'];
        }
        if (!empty($filtros['status'])) {
            $condicoes[] = 'status = :status';
            $parametros[':status'] = $filtros['status'];
        }
        if (!empty($filtros['data_inicio'])) {
            $condicoes[] = 'criado_em >= :data_inicio';
            $parametros[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
        }

        $sql = 'SELECT * FROM vw_execucoes_detalhadas WHERE ' . implode(' AND ', $condicoes)
             . ' ORDER BY criado_em DESC LIMIT :limite';

        $consulta = $this->pdo->prepare($sql);
        foreach ($parametros as $chave => $valor) {
            $consulta->bindValue($chave, $valor, PDO::PARAM_STR);
        }
        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll();
    }
}
