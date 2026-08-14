<?php

/**
 * teste_carregamento_classes.php
 *
 * Roda `spl_autoload_register` na mão (sem Composer, que não tem
 * como instalar aqui) e tenta carregar TODAS as classes do projeto.
 * Isso pega erro de classe não encontrada, herança quebrada
 * (extends/implements de algo que não existe) e métodos abstratos
 * que ficaram sem implementar - tudo isso sem precisar de conexão
 * com banco, porque só carrega a classe, não instancia ela.
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

function listarClasses(string $pasta): array
{
    $classes = [];
    foreach (glob($pasta . '/*/*.php') as $arquivo) {
        $relativo = str_replace([$pasta . '/', '.php'], '', $arquivo);
        $classes[] = 'Udflow\\' . str_replace('/', '\\', $relativo);
    }
    return $classes;
}

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

echo "== Carregando todas as classes ==\n";
$classes = listarClasses(__DIR__ . '/src');
foreach ($classes as $classe) {
    try {
        conferir("carrega {$classe}", class_exists($classe));
    } catch (\Throwable $e) {
        conferir("carrega {$classe} (ERRO: " . $e->getMessage() . ")", false);
    }
}

echo "\n== AutomacaoController e as 3 automações que herdam dele ==\n";
$controllerBase = new \ReflectionClass('Udflow\\controller\\AutomacaoController');
conferir('AutomacaoController é abstrata', $controllerBase->isAbstract());

foreach (['KpiController', 'MaoObraController', 'EstadiaController'] as $nomeClasse) {
    $classe = "Udflow\\controller\\{$nomeClasse}";
    $reflexao = new \ReflectionClass($classe);
    conferir("{$nomeClasse} não é abstrata (implementou tudo)", !$reflexao->isAbstract());
    conferir("{$nomeClasse} é subclasse de AutomacaoController", $reflexao->isSubclassOf($controllerBase->getName()));

    foreach (['chave', 'rn', 'view'] as $metodo) {
        $declaradoEm = $reflexao->getMethod($metodo)->getDeclaringClass()->getName();
        conferir("{$nomeClasse}::{$metodo}() foi sobrescrito (não herdado da base)", $declaradoEm === $classe);
    }
}

echo "\n== ExecucaoRn e KpiExecucaoRn ==\n";
$rnBase = new \ReflectionClass('Udflow\\rn\\ExecucaoRn');
$rnKpi = new \ReflectionClass('Udflow\\rn\\KpiExecucaoRn');
conferir('KpiExecucaoRn é subclasse de ExecucaoRn', $rnKpi->isSubclassOf($rnBase->getName()));
conferir('KpiExecucaoRn sobrescreve validarEmailDestino', $rnKpi->getMethod('validarEmailDestino')->getDeclaringClass()->getName() === 'Udflow\\rn\\KpiExecucaoRn');

echo "\n== Métodos estáticos usados nas views/JS não quebraram ao virar static ==\n";
conferir('AutenticacaoRn::regrasDeSenha é estático', (new \ReflectionMethod('Udflow\\rn\\AutenticacaoRn', 'regrasDeSenha'))->isStatic());
conferir('UsuarioRn::sugerirLogin é estático', (new \ReflectionMethod('Udflow\\rn\\UsuarioRn', 'sugerirLogin'))->isStatic());
conferir('ClienteRn::normalizarCnpj é estático', (new \ReflectionMethod('Udflow\\rn\\ClienteRn', 'normalizarCnpj'))->isStatic());

echo "\n============================\n";
echo "{$totalTestes} conferências, {$totalFalhas} falha(s)\n";
exit($totalFalhas > 0 ? 1 : 0);
