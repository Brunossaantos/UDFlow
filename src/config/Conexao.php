<?php

namespace Udflow\config;

use PDO;
use PDOException;

/**
 * Conexao
 *
 * Só existe pra dar um PDO configurado direito pro resto do sistema.
 * Um objeto só por requisição (singleton) - não faz sentido abrir
 * uma conexão nova toda vez que um Dao precisa consultar o banco.
 *
 * Todo Dao do UDFlow usa PDO com prepared statement. Isso aqui não é
 * frescura: é o que impede injeção de SQL - a gente nunca monta a
 * query concatenando string com o que o usuário digitou.
 */
class Conexao
{
    private static ?PDO $instancia = null;

    private function __construct()
    {
        // ninguém instancia essa classe direto, só via pegar()
    }

    public static function pegar(): PDO
    {
        if (self::$instancia === null) {
            $host  = $_ENV['DB_HOST'];
            $porta = $_ENV['DB_PORT'] ?? '3306';
            $nome  = $_ENV['DB_NOME'];

            $dsn = "mysql:host={$host};port={$porta};dbname={$nome};charset=utf8mb4";

            try {
                self::$instancia = new PDO($dsn, $_ENV['DB_USUARIO'], $_ENV['DB_SENHA'] ?? '', [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false, // deixa o MariaDB preparar de verdade, mais seguro
                ]);

                // Brasil não observa horário de verão desde 2019, então um
                // offset fixo (-03:00) é seguro e não depende das tabelas
                // de fuso horário nomeado do MySQL estarem carregadas -
                // muita hospedagem compartilhada não tem isso configurado.
                // Sem isso, o NOW()/CURRENT_TIMESTAMP do banco roda no
                // fuso do sistema operacional do servidor (geralmente UTC),
                // divergindo do fuso que o PHP usa (America/Sao_Paulo) -
                // e qualquer comparação de prazo (tipo o código de
                // redefinição de senha) ou data exibida na tela fica errada.
                self::$instancia->exec("SET time_zone = '-03:00'");
            } catch (PDOException $e) {
                // não expõe a mensagem de erro do PDO (pode vazar host/usuário do banco)
                error_log('Falha ao conectar no banco: ' . $e->getMessage());
                throw new PDOException('Não foi possível conectar ao banco de dados.');
            }
        }

        return self::$instancia;
    }
}
