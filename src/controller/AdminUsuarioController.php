<?php

namespace Udflow\controller;

use Udflow\rn\UsuarioRn;
use Udflow\dao\AutomacaoDao;
use Udflow\util\Csrf;
use Udflow\util\Saida;
use Udflow\util\ControleAcesso;

/**
 * AdminUsuarioController
 *
 * Só super_admin mexe aqui - criar usuário e mudar quem é admin de
 * quê é o tipo de poder que não dá pra deixar solto pra qualquer
 * admin comum.
 */
class AdminUsuarioController
{
    public function tela(): void
    {
        ControleAcesso::exigirSuperAdmin();

        $usuarioRn = new UsuarioRn();
        $usuarios = $usuarioRn->listarComPermissoes();
        $automacoes = (new AutomacaoDao())->listarTodas();

        require __DIR__ . '/../../views/admin/usuarios.php';
    }

    public function criar(): void
    {
        ControleAcesso::exigirSuperAdmin();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            $this->voltarComErro('Sessão expirada, atualiza a página.');
        }

        $nome = trim($_POST['nome'] ?? '');
        $login = trim($_POST['usuario'] ?? '');
        $emailPessoal = trim($_POST['email'] ?? '');
        $superAdmin = !empty($_POST['super_admin']);
        $permissoes = $_POST['permissoes'] ?? []; // [automacao_id => papel]

        $resultado = (new UsuarioRn())->criar(
            $nome,
            $login,
            $emailPessoal,
            $superAdmin,
            $permissoes,
            ControleAcesso::usuarioLogadoId()
        );

        if (!$resultado['sucesso']) {
            $this->voltarComErro($resultado['mensagem']);
        }

        $_SESSION['flash_sucesso'] = "Usuário {$login} criado com a senha provisória Udlog123.";
        header('Location: index.php?pagina=admin-usuarios');
        exit;
    }

    public function alternarAtivo(): void
    {
        ControleAcesso::exigirSuperAdmin();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        $ativo = ($_POST['ativo'] ?? '') === '1';

        if ($usuarioId <= 0) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Usuário inválido.'], 422);
        }

        (new UsuarioRn())->alternarAtivo($usuarioId, $ativo, ControleAcesso::usuarioLogadoId());

        Saida::json(['sucesso' => true]);
    }

    public function atualizarPermissoes(): void
    {
        ControleAcesso::exigirSuperAdmin();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            $this->voltarComErro('Sessão expirada, atualiza a página.');
        }

        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        $permissoes = $_POST['permissoes'] ?? [];

        if ($usuarioId <= 0) {
            $this->voltarComErro('Usuário inválido.');
        }

        (new UsuarioRn())->atualizarPermissoes($usuarioId, $permissoes, ControleAcesso::usuarioLogadoId());

        $_SESSION['flash_sucesso'] = 'Permissões atualizadas.';
        header('Location: index.php?pagina=admin-usuarios');
        exit;
    }

    private function voltarComErro(string $mensagem)
    {
        $_SESSION['flash_erro'] = $mensagem;
        header('Location: index.php?pagina=admin-usuarios');
        exit;
    }
}
