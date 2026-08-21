<?php

namespace Udflow\dao;

use Udflow\config\Conexao;
use PDO;

/**
 * LogSistemaDao
 *
 * Guarda erros/exceptions/fatais do sistema, capturados pelos
 * manipuladores globais (LogSistema::registrarManipuladoresGlobais)
 * ou registrados manualmente em pontos de falha de negócio.
 */
class LogSistemaDao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::pegar();
    }

    public function registrar(string $nivel, string $mensagem, ?string $arquivo, ?int $linha, ?array $contexto): void
    {
        $sql = 'INSERT INTO tb_logs_sistema (nivel, mensagem, arquivo, linha, contexto)
                VALUES (:nivel, :mensagem, :arquivo, :linha, :contexto)';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':nivel', $nivel, PDO::PARAM_STR);
        $comando->bindValue(':mensagem', $mensagem, PDO::PARAM_STR);
        $comando->bindValue(':arquivo', $arquivo, $arquivo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->bindValue(':linha', $linha, $linha === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $comando->bindValue(':contexto', $contexto ? json_encode($contexto, JSON_UNESCAPED_UNICODE) : null, PDO::PARAM_STR);
        $comando->execute();
    }

    /**
     * @param array{nivel?: ?string, data_inicio?: ?string} $filtros
     */
    public function listarComFiltros(array $filtros = [], int $limite = 200): array
    {
        $condicoes = [];
        $parametros = [];

        if (!empty($filtros['nivel'])) {
            $condicoes[] = 'nivel = :nivel';
            $parametros[':nivel'] = $filtros['nivel'];
        }

        if (!empty($filtros['data_inicio'])) {
            $condicoes[] = 'criado_em >= :data_inicio';
            $parametros[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
        }

        $where = $condicoes ? ('WHERE ' . implode(' AND ', $condicoes)) : '';

        $sql = "SELECT * FROM tb_logs_sistema {$where} ORDER BY criado_em DESC LIMIT :limite";

        $consulta = $this->pdo->prepare($sql);
        foreach ($parametros as $chave => $valor) {
            $consulta->bindValue($chave, $valor, PDO::PARAM_STR);
        }
        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function contarPorNivel(): array
    {
        $sql = 'SELECT nivel, COUNT(*) AS total FROM tb_logs_sistema GROUP BY nivel';

        $consulta = $this->pdo->query($sql);

        return $consulta ? $consulta->fetchAll(PDO::FETCH_KEY_PAIR) : [];
    }

    public function limparAntigos(int $diasParaManter = 90): void
    {
        $sql = 'DELETE FROM tb_logs_sistema WHERE criado_em < DATE_SUB(NOW(), INTERVAL :dias DAY)';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':dias', $diasParaManter, PDO::PARAM_INT);
        $comando->execute();
    }
}
