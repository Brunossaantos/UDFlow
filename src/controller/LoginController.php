<?php

namespace Udflow\controller;

use Udflow\rn\AutenticacaoRn;
use Udflow\rn\RedefinicaoSenhaRn;
use Udflow\util\Csrf;
use Udflow\util\ControleAcesso;

class LoginController
{
    public function tela(): void
    {
        if (ControleAcesso::estaLogado()) {
            header('Location: index.php?pagina=home');
            exit;
        }

        require __DIR__ . '/../../views/login.php';
    }

    public function entrar(): void
    {
        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            $this->voltarComErro('Sessão expirada, atualiza a página e tenta de novo.');
        }

        $usuario = trim($_POST['usuario'] ?? '');
        $senha = (string) ($_POST['senha'] ?? '');

        if ($usuario === '' || $senha === '') {
            $this->voltarComErro('Preenche usuário e senha.');
        }

        $autenticacaoRn = new AutenticacaoRn();
        $resultado = $autenticacaoRn->autenticar($usuario, $senha);

        if (!$resultado['sucesso']) {
            $this->voltarComErro($resultado['mensagem']);
        }

        $autenticacaoRn->iniciarSessao($resultado['usuario']);

        if ($resultado['usuario']->trocarSenhaNoLogin) {
            header('Location: index.php?pagina=trocar-senha');
            exit;
        }

        header('Location: index.php?pagina=home');
        exit;
    }

    public function sair(): void
    {
        (new AutenticacaoRn())->encerrarSessao();
        header('Location: index.php?pagina=login');
        exit;
    }

    /** Tela de troca obrigatória - cai aqui logo depois do login quando a senha ainda é a provisória */
    public function telaTrocarSenha(): void
    {
        require __DIR__ . '/../../views/trocar-senha.php';
    }

    public function trocarSenha(): void
    {
        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            $this->voltarComErro('Sessão expirada, atualiza a página.', 'trocar-senha');
        }

        $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
        $senhaNova = (string) ($_POST['senha_nova'] ?? '');
        $confirmacao = (string) ($_POST['senha_confirmacao'] ?? '');

        if ($senhaNova !== $confirmacao) {
            $this->voltarComErro('A confirmação não bate com a senha nova.', 'trocar-senha');
        }

        $usuarioId = $_SESSION['usuario']['id'] ?? null;
        $resultado = (new AutenticacaoRn())->trocarSenha((int) $usuarioId, $senhaAtual, $senhaNova);

        if (!$resultado['sucesso']) {
            $this->voltarComErro($resultado['mensagem'], 'trocar-senha');
        }

        $_SESSION['usuario']['trocar_senha_no_login'] = false;
        $_SESSION['flash_sucesso'] = 'Senha alterada com sucesso.';
        header('Location: index.php?pagina=home');
        exit;
    }

    public function telaEsqueciSenha(): void
    {
        require __DIR__ . '/../../views/esqueci-senha.php';
    }

    public function enviarCodigo(): void
    {
        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            $this->voltarComErro('Sessão expirada, atualiza a página e tenta de novo.', 'esqueci-senha');
        }

        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->voltarComErro('Informe um e-mail válido.', 'esqueci-senha');
        }

        $codigo = (new RedefinicaoSenhaRn())->solicitarCodigo($email);

        // manda o e-mail só se o código foi gerado (usuário existe) -
        // mas a tela mostra sempre a mesma mensagem de sucesso, pra
        // não revelar se aquele e-mail está cadastrado ou não
        if ($codigo !== null) {
            // TODO: chamar o serviço de e-mail (SMTP configurado no .env)
            // com o código pro destinatário
        }

        $_SESSION['email_redefinicao'] = $email;
        header('Location: index.php?pagina=redefinir-senha');
        exit;
    }

    public function telaRedefinirSenha(): void
    {
        if (empty($_SESSION['email_redefinicao'])) {
            header('Location: index.php?pagina=esqueci-senha');
            exit;
        }

        require __DIR__ . '/../../views/redefinir-senha.php';
    }

    public function confirmarRedefinicao(): void
    {
        if (!Csrf::validarToken($_POST['csrf_token'] ?? null) || empty($_SESSION['email_redefinicao'])) {
            $this->voltarComErro('Sessão expirada, começa de novo.', 'esqueci-senha');
        }

        $codigo = trim($_POST['codigo'] ?? '');
        $senhaNova = (string) ($_POST['senha_nova'] ?? '');

        $resultado = (new RedefinicaoSenhaRn())->confirmarRedefinicao(
            $_SESSION['email_redefinicao'],
            $codigo,
            $senhaNova
        );

        if (!$resultado['sucesso']) {
            $this->voltarComErro($resultado['mensagem'], 'redefinir-senha');
        }

        unset($_SESSION['email_redefinicao']);
        $_SESSION['flash_sucesso'] = 'Senha redefinida. Já pode entrar com a senha nova.';
        header('Location: index.php?pagina=login');
        exit;
    }

    private function voltarComErro(string $mensagem, string $pagina = 'login')
    {
        $_SESSION['flash_erro'] = $mensagem;
        header("Location: index.php?pagina={$pagina}");
        exit;
    }
}
