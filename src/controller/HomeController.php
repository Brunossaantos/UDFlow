<?php

namespace Udflow\controller;

use Udflow\dao\PermissaoDao;
use Udflow\util\ControleAcesso;

class HomeController
{
    public function tela(): void
    {
        ControleAcesso::exigirLogin();

        $permissaoDao = new PermissaoDao();
        $automacoes = $permissaoDao->automacoesVisiveisParaUsuario(ControleAcesso::usuarioLogadoId());

        require __DIR__ . '/../../views/home.php';
    }
}
