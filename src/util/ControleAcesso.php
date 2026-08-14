<?php

namespace Udflow\util;

use Udflow\dao\PermissaoDao;

/**
 * ControleAcesso
 *
 * Ponto único onde a gente decide se a requisição pode seguir ou
 * não. O index.php chama isso antes de entrar em qualquer
 * Controller - assim ninguém esquece de checar permissão dentro de
 * um Controller específico, porque a checagem nem chega lá se
 * falhar aqui.
 */
class ControleAcesso
{
    public static function estaLogado(): bool
    {
        return !empty($_SESSION['usuario']['id']);
    }

    public static function usuarioLogadoId(): ?int
    {
        return $_SESSION['usuario']['id'] ?? null;
    }

    public static function usuarioEhSuperAdmin(): bool
    {
        return !empty($_SESSION['usuario']['super_admin']);
    }

    /** Páginas que continuam acessíveis mesmo quando a senha ainda é a provisória */
    private const PAGINAS_LIVRES_DE_TROCA_DE_SENHA = ['trocar-senha', 'trocar-senha-salvar', 'sair'];

    public static function exigirLogin(): void
    {
        if (!self::estaLogado()) {
            header('Location: index.php?pagina=login');
            exit;
        }

        $precisaTrocarSenha = !empty($_SESSION['usuario']['trocar_senha_no_login']);
        $paginaAtual = $_GET['pagina'] ?? '';

        if ($precisaTrocarSenha && !in_array($paginaAtual, self::PAGINAS_LIVRES_DE_TROCA_DE_SENHA, true)) {
            header('Location: index.php?pagina=trocar-senha');
            exit;
        }
    }

    /**
     * Confere se o usuário logado tem, no mínimo, o papel informado
     * numa automação. super_admin sempre passa.
     */
    public static function exigirPapel(string $automacaoChave, string $papelMinimo): void
    {
        self::exigirLogin();

        if (self::usuarioEhSuperAdmin()) {
            return;
        }

        $permissaoDao = new PermissaoDao();
        $papelDoUsuario = $permissaoDao->papelDoUsuarioNaAutomacao(self::usuarioLogadoId(), $automacaoChave);

        $niveis = ['usuario' => 1, 'admin' => 2];
        $nivelNecessario = $niveis[$papelMinimo] ?? 99;
        $nivelDoUsuario = $niveis[$papelDoUsuario] ?? 0;

        if ($nivelDoUsuario < $nivelNecessario) {
            http_response_code(403);
            echo 'Você não tem permissão pra acessar essa página.';
            exit;
        }
    }

    public static function exigirSuperAdmin(): void
    {
        self::exigirLogin();

        if (!self::usuarioEhSuperAdmin()) {
            http_response_code(403);
            echo 'Só o super administrador pode acessar essa página.';
            exit;
        }
    }

    /**
     * Usado nas telas de admin que não são exclusivas de super_admin
     * (Logs, Clientes, Cronograma) - libera pra quem é admin em pelo
     * menos uma automação, já que hoje todo admin acaba sendo admin
     * de tudo mesmo.
     */
    public static function exigirAdminDeAlgumaAutomacao(): void
    {
        self::exigirLogin();

        if (self::usuarioEhSuperAdmin()) {
            return;
        }

        $permissaoDao = new PermissaoDao();
        if (!$permissaoDao->usuarioEhAdminDeAlgumaAutomacao(self::usuarioLogadoId())) {
            http_response_code(403);
            echo 'Você não tem permissão pra acessar essa página.';
            exit;
        }
    }
}
