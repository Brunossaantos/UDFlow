<?php

/**
 * teste_renderizar_views.php
 *
 * Renderiza de verdade as views que não precisam de banco (as
 * telas de login e recuperação de senha, que ficam fora da
 * sidebar). Pega erro de variável não definida, chamada de método
 * errado, HTML mal fechado que quebra o PHP, etc. - coisa que só
 * aparece rodando de verdade, não só com php -l.
 *
 * As views logadas (home, kpi, admin/*) dependem de
 * Udflow\dao\PermissaoDao, que por sua vez precisa de conexão real
 * com o MariaDB - por isso elas ficam de fora desse teste e
 * precisam ser conferidas no ambiente real (XAMPP/Hostgator) com o
 * banco no ar.
 */

spl_autoload_register(function ($classe) {
    $prefixo = 'Udflow\\';
    if (!str_starts_with($classe, $prefixo)) {
        return;
    }
    $caminhoRelativo = str_replace('\\', '/', substr($classe, strlen($prefixo)));
    $arquivo = __DIR__ . '/src/' . $caminhoRelativo . '.php';
    if (file_exists($arquivo)) {
        require $arquivo;
    }
});

session_start();
$_SESSION['csrf_token'] = 'token-de-teste';

$totalTestes = 0;
$totalFalhas = 0;

function conferir(string $descricao, bool $condicao): void
{
    global $totalTestes, $totalFalhas;
    $totalTestes++;
    if ($condicao) {
        echo "  OK  - {$descricao}\n";
    } else {
        echo "FALHA - {$descricao}\n";
        $totalFalhas++;
    }
}

function renderizar(string $arquivo): string
{
    ob_start();
    require $arquivo;
    return ob_get_clean();
}

echo "== login.php ==\n";
$html = renderizar(__DIR__ . '/views/login.php');
conferir('renderizou sem quebrar', str_contains($html, '<html'));
conferir('tem o campo de usuário', str_contains($html, 'name="usuario"'));
conferir('tem o token CSRF no formulário', str_contains($html, 'token-de-teste'));
conferir('link de redefinir senha aponta pra rota certa', str_contains($html, 'pagina=esqueci-senha'));

echo "\n== esqueci-senha.php ==\n";
$html = renderizar(__DIR__ . '/views/esqueci-senha.php');
conferir('renderizou sem quebrar', str_contains($html, '<html'));
conferir('formulário aponta pra rota de envio de código', str_contains($html, 'pagina=esqueci-senha-enviar'));

echo "\n== redefinir-senha.php ==\n";
$_SESSION['email_redefinicao'] = 'bruno.teste@gmail.com';
$html = renderizar(__DIR__ . '/views/redefinir-senha.php');
conferir('renderizou sem quebrar', str_contains($html, '<html'));
conferir('mostra o e-mail em andamento', str_contains($html, 'bruno.teste@gmail.com'));
conferir('formulário aponta pra rota de confirmação', str_contains($html, 'pagina=redefinir-senha-confirmar'));

echo "\n== trocar-senha.php ==\n";
$html = renderizar(__DIR__ . '/views/trocar-senha.php');
conferir('renderizou sem quebrar', str_contains($html, '<html'));
conferir('tem os 3 campos de senha', substr_count($html, 'type="password"') === 3);
conferir('formulário aponta pra rota de salvar', str_contains($html, 'pagina=trocar-senha-salvar'));

echo "\n============================\n";
echo "{$totalTestes} conferências, {$totalFalhas} falha(s)\n";
exit($totalFalhas > 0 ? 1 : 0);
