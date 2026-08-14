<?php

namespace Udflow\dao;

use Udflow\config\Conexao;
use PDO;

class UnidadeDao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::pegar();
    }

    public function listarTodas(): array
    {
        return $this->pdo->query('SELECT * FROM tb_unidades WHERE ativo = 1 ORDER BY nome')->fetchAll();
    }
}
