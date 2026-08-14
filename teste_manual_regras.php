<?php

/**
 * teste_manual_regras.php
 *
 * Não é um teste automatizado de verdade (isso pede PHPUnit, que
 * fica pra depois) - é um script que qualquer um roda com
 * `php teste_manual_regras.php` pra conferir rapidinho se as regras
 * que NÃO dependem de banco continuam se comportando como esperado
 * depois de alguma mudança. Cobre exatamente as partes que dava pra
 * testar sem MariaDB de verdade rodando.
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

use Udflow\util\Csrf;
use Udflow\util\ProtecaoForcaBruta;
use Udflow\rn\AutenticacaoRn;
use Udflow\rn\UsuarioRn;
use Udflow\rn\ClienteRn;

$totalTestes = 0;
$totalFalhas = 0;

session_start();

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

echo "== Csrf ==\n";
$token = Csrf::gerarToken();
conferir('token gerado não é vazio', $token !== '');
conferir('token válido é aceito', Csrf::validarToken($token));
conferir('token errado é recusado', !Csrf::validarToken('token-invento-aqui'));
conferir('token vazio é recusado', !Csrf::validarToken(''));
conferir('token nulo é recusado', !Csrf::validarToken(null));

echo "\n== ProtecaoForcaBruta ==\n";
$_SESSION = []; // começa limpo
conferir('pode tentar antes de qualquer falha', ProtecaoForcaBruta::podeTentar());
for ($i = 0; $i < 5; $i++) {
    ProtecaoForcaBruta::registrarFalha();
}
conferir('bloqueia depois de 5 falhas seguidas', !ProtecaoForcaBruta::podeTentar());
conferir('segundos restantes é maior que zero durante o bloqueio', ProtecaoForcaBruta::segundosRestantesDeBloqueio() > 0);
ProtecaoForcaBruta::registrarSucesso();
conferir('sucesso libera de novo', ProtecaoForcaBruta::podeTentar());

echo "\n== AutenticacaoRn::regrasDeSenha ==\n";
conferir('senha curta é recusada', AutenticacaoRn::regrasDeSenha('abc123') !== null);
conferir('senha só com letra é recusada', AutenticacaoRn::regrasDeSenha('somenteletras') !== null);
conferir('senha só com número é recusada', AutenticacaoRn::regrasDeSenha('12345678') !== null);
conferir('senha boa passa', AutenticacaoRn::regrasDeSenha('udlog2026') === null);

echo "\n== UsuarioRn::sugerirLogin ==\n";
conferir('nome com sobrenome vira primeiro.ultimo', UsuarioRn::sugerirLogin('Bruno Carvalho') === 'bruno.carvalho');
conferir('nome com acento perde o acento', UsuarioRn::sugerirLogin('João da Conceição') === 'joao.conceicao');
conferir('nome único não quebra', UsuarioRn::sugerirLogin('Madonna') === 'madonna');
conferir('nome vazio devolve vazio', UsuarioRn::sugerirLogin('') === '');

echo "\n== ClienteRn::normalizarCnpj ==\n";
conferir('CNPJ com máscara vira só dígitos', ClienteRn::normalizarCnpj('12.345.678/0001-99') === '12345678000199');
conferir('CNPJ com menos de 14 dígitos é rejeitado', ClienteRn::normalizarCnpj('123') === null);
conferir('CNPJ com mais de 14 dígitos é rejeitado', ClienteRn::normalizarCnpj('123456780001999999') === null);

echo "\n== regex de e-mail @udlog (mesma regra usada em KpiExecucaoRn) ==\n";
$regexUdlog = '/@udlog\.[a-z.]+$/i';
conferir('aceita nome@udlog.com', preg_match($regexUdlog, 'financeiro@udlog.com') === 1);
conferir('aceita nome@udlog.online', preg_match($regexUdlog, 'bruno.santos@udlog.online') === 1);
conferir('aceita nome@udlog.com.br', preg_match($regexUdlog, 'ops@udlog.com.br') === 1);
conferir('recusa gmail', preg_match($regexUdlog, 'fulano@gmail.com') === 0);
conferir('recusa domínio que só contém "udlog" como parte do nome (naoudlog.com)', preg_match($regexUdlog, 'fulano@naoudlog.com') === 0);

echo "\n============================\n";
echo "{$totalTestes} conferências, {$totalFalhas} falha(s)\n";
exit($totalFalhas > 0 ? 1 : 0);
