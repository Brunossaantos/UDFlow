<?php

/**
 * teste_views_logadas.php
 *
 * Renderiza de verdade as telas que dependem do banco (Home, KPI e
 * as 5 telas de admin), simulando uma sessão de usuário logado.
 * Isso pega erro de variável não definida, chamada errada de
 * método, campo com nome trocado entre o Controller e a view - todo
 * tipo de bug que só aparece rodando pra valer.
 */

foreach (file(__DIR__ . '/.env') as $linha) {
    $linha = trim($linha);
    if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) continue;
    [$chave, $valor] = explode('=', $linha, 2);
    $_ENV[trim($chave)] = trim($valor);
}

spl_autoload_register(function ($classe) {
    $prefixo = 'Udflow\\';
    if (!str_starts_with($classe, $prefixo)) return;
    $caminho = str_replace('\\', '/', substr($classe, strlen($prefixo)));
    $arquivo = __DIR__ . '/src/' . $caminho . '.php';
    if (file_exists($arquivo)) require $arquivo;
});

session_start();
date_default_timezone_set('America/Sao_Paulo');

use Udflow\dao\UsuarioDao;
use Udflow\dao\AutomacaoDao;
use Udflow\dao\PermissaoDao;
use Udflow\dao\ClienteDao;
use Udflow\dao\ExecucaoDao;
use Udflow\dao\UnidadeDao;
use Udflow\dao\CronogramaDao;
use Udflow\rn\UsuarioRn;
use Udflow\rn\ClienteRn;
use Udflow\rn\AutomacaoRn;
use Udflow\rn\CronogramaRn;
use Udflow\util\Csrf;

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

function renderizar(string $arquivo, array $variaveis = []): string
{
    extract($variaveis);
    ob_start();
    require $arquivo;
    return ob_get_clean();
}

// --- monta uma massa de dados mínima pra ter algo pra ver na tela ---
$usuarioRn = new UsuarioRn();
$resultadoSuperAdmin = $usuarioRn->criar('Bruno Teste', 'bruno.teste', 'bruno.teste@gmail.com', true, [], 0);
$superAdminId = $resultadoSuperAdmin['usuarioId'];

$kpi = (new AutomacaoDao())->buscarPorChave('kpi');
$maoObra = (new AutomacaoDao())->buscarPorChave('mao_obra_batida');
$unidades = (new UnidadeDao())->listarTodas();

$clienteRn = new ClienteRn();
$resultadoCliente = $clienteRn->criar([
    'unidade_id' => $unidades[0]['id'],
    'codigo_cliente' => 'TESTE_VIEW',
    'razao_social' => 'Cliente de Teste das Views Ltda',
    'nome_exibicao' => 'Cliente Teste Views',
    'cnpj' => '11.222.333/0001-44',
    'email_responsavel' => 'responsavel@testeviews.com',
    'logo_url' => '',
    'cor_primaria' => '#0B6FA4',
    'cor_secundaria' => '#64748B',
    'ativo' => '1',
], [(int) $kpi['id'] => true], $superAdminId);
$clienteId = $resultadoCliente['clienteId'];

(new CronogramaDao())->criar((int) $maoObra['id'], $clienteId, 10, '07:00:00');

$_SESSION['usuario'] = [
    'id' => $superAdminId,
    'nome' => 'Bruno Teste',
    'usuario' => 'bruno.teste',
    'super_admin' => true,
    'trocar_senha_no_login' => false,
];
Csrf::gerarToken();

echo "== home.php ==\n";
$automacoesHome = (new PermissaoDao())->automacoesVisiveisParaUsuario($superAdminId);
$html = renderizar(__DIR__ . '/views/home.php', ['automacoes' => $automacoesHome]);
conferir('renderizou sem quebrar', str_contains($html, '<html'));
conferir('mostra o nome do usuário logado', str_contains($html, 'Bruno Teste'));
conferir('sidebar lista as 3 automações (super_admin enxerga tudo)', substr_count($html, 'Abrir automação') === 3);

echo "\n== kpi.php ==\n";
$minhasExecucoesKpi = (new ExecucaoDao())->listarDoUsuario($superAdminId, 'kpi');
$html = renderizar(__DIR__ . '/views/kpi.php', ['minhasExecucoes' => $minhasExecucoesKpi]);
conferir('renderizou sem quebrar', str_contains($html, '<html'));
conferir('mostra o aviso de e-mail @udlog (exclusivo do KPI)', str_contains($html, 'Somente e-mails @udlog'));
conferir('botão certo pro KPI (Gerar e enviar)', str_contains($html, 'Gerar e enviar'));

echo "\n== mao-obra.php ==\n";
$minhasExecucoesMao = (new ExecucaoDao())->listarDoUsuario($superAdminId, 'mao_obra_batida');
$html = renderizar(__DIR__ . '/views/mao-obra.php', ['minhasExecucoes' => $minhasExecucoesMao]);
conferir('renderizou sem quebrar', str_contains($html, '<html'));
conferir('NÃO mostra aviso de @udlog (não é exclusivo dela)', !str_contains($html, 'Somente e-mails @udlog'));
conferir('botão certo (Executar agora)', str_contains($html, 'Executar agora'));
conferir('mostra aviso de agendamento automático', str_contains($html, 'roda sozinha'));

echo "\n== admin/usuarios.php ==\n";
$usuariosComPermissoes = $usuarioRn->listarComPermissoes();
$automacoesTodas = (new AutomacaoDao())->listarTodas();
$html = renderizar(__DIR__ . '/views/admin/usuarios.php', ['usuarios' => $usuariosComPermissoes, 'automacoes' => $automacoesTodas]);
conferir('renderizou sem quebrar', str_contains($html, '<html'));
conferir('lista o usuário criado', str_contains($html, 'bruno.teste'));
conferir('mostra a senha provisória padrão no modal', str_contains($html, 'Udlog123'));

echo "\n== admin/clientes.php ==\n";
$clienteDao = new ClienteDao();
$clientesComDetalhes = array_map(fn ($c) => [
    'cliente' => $c,
    'kpiConfig' => $clienteDao->buscarKpiConfig($c->id),
    'automacoesAtivas' => $clienteDao->automacoesDoCliente($c->id),
], $clienteDao->listarTodos());
$html = renderizar(__DIR__ . '/views/admin/clientes.php', ['clientesComDetalhes' => $clientesComDetalhes, 'unidades' => $unidades, 'automacoes' => $automacoesTodas]);
conferir('renderizou sem quebrar', str_contains($html, '<html'));
conferir('lista o cliente criado', str_contains($html, 'Cliente Teste Views'));
conferir('mostra a badge de unidade no card', str_contains($html, Udflow\util\Saida::e($unidades[0]['nome'])));

echo "\n== admin/automacoes.php ==\n";
$html = renderizar(__DIR__ . '/views/admin/automacoes.php', ['automacoes' => (new AutomacaoRn())->listar()]);
conferir('renderizou sem quebrar', str_contains($html, '<html'));
conferir('lista as 3 automações (3 checkboxes de visibilidade)', substr_count($html, 'onchange="alternarVisibilidade(') === 3);

echo "\n== admin/logs.php ==\n";
$_GET = [];
$html = renderizar(__DIR__ . '/views/admin/logs.php', ['execucoes' => (new ExecucaoDao())->listarComFiltros(), 'automacoes' => $automacoesTodas]);
conferir('renderizou sem quebrar', str_contains($html, '<html'));

echo "\n== admin/cronograma.php ==\n";
$_GET = [];
$html = renderizar(__DIR__ . '/views/admin/cronograma.php', [
    'cronograma' => (new CronogramaRn())->listar(),
    'automacoes' => array_filter($automacoesTodas, fn ($a) => $a['possui_agendamento']),
    'unidades' => $unidades,
]);
conferir('renderizou sem quebrar', str_contains($html, '<html'));
conferir('mostra o item de cronograma criado', str_contains($html, 'Cliente Teste Views'));
conferir('mostra o aviso sobre a integração com o n8n ainda não estar pronta', str_contains($html, 'ainda não desliga o cron'));

echo "\n============================\n";
echo "{$totalTestes} conferências, {$totalFalhas} falha(s)\n";
exit($totalFalhas > 0 ? 1 : 0);
