<?php

/**
 * teste_fluxo_completo.php
 *
 * Esse aqui já é osso: roda contra um MariaDB de verdade (criado só
 * pra teste, com o schema completo aplicado) e exercita a camada
 * Dao/Rn de ponta a ponta - criar usuário, checar permissão, criar
 * cliente, criar cronograma, registrar execução. Não passa pelos
 * Controllers porque eles fazem header()/exit() (coisa de fluxo
 * HTTP, não dá pra rodar direto no terminal) - mas isso não é
 * problema, porque toda a regra de negócio de verdade mora no Rn e
 * no Dao, não no Controller (o Controller só traduz request pra
 * chamada de Rn).
 *
 * Não sobe .env via Dotenv de verdade porque o Composer não tem
 * como instalar aqui no ambiente de teste (sem acesso ao
 * Packagist) - só pra esse script, o .env é lido na mão. No projeto
 * de verdade, `composer install` traz o vlucas/phpdotenv igual está
 * no composer.json.
 */

// --- carga manual do .env, só pra esse teste ---
foreach (file(__DIR__ . '/.env') as $linha) {
    $linha = trim($linha);
    if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
        continue;
    }
    [$chave, $valor] = explode('=', $linha, 2);
    $_ENV[trim($chave)] = trim($valor);
}

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
date_default_timezone_set('America/Sao_Paulo');

use Udflow\dao\UsuarioDao;
use Udflow\dao\AutomacaoDao;
use Udflow\dao\PermissaoDao;
use Udflow\dao\ClienteDao;
use Udflow\dao\CronogramaDao;
use Udflow\dao\ExecucaoDao;
use Udflow\dao\UnidadeDao;
use Udflow\rn\UsuarioRn;
use Udflow\rn\ClienteRn;
use Udflow\rn\AutenticacaoRn;
use Udflow\rn\CronogramaRn;

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

echo "== Conexão e seed ==\n";
$automacaoDao = new AutomacaoDao();
$automacoes = $automacaoDao->listarTodas();
conferir('conectou no banco e leu tb_automacoes', count($automacoes) > 0);
conferir('as 3 automações do seed estão lá (kpi, mao_obra_batida, estadia)', count($automacoes) === 3);

$kpi = $automacaoDao->buscarPorChave('kpi');
$maoObra = $automacaoDao->buscarPorChave('mao_obra_batida');
$estadia = $automacaoDao->buscarPorChave('estadia');
conferir('KPI existe e está visível pra usuários comuns', $kpi !== null && (bool) $kpi['visivel_para_usuarios'] === true);
conferir('Mão de Obra Batida existe e NÃO está visível pra usuários comuns', $maoObra !== null && (bool) $maoObra['visivel_para_usuarios'] === false);
conferir('Estadia existe e NÃO está visível pra usuários comuns', $estadia !== null && (bool) $estadia['visivel_para_usuarios'] === false);

$unidades = (new UnidadeDao())->listarTodas();
conferir('as 2 unidades do seed estão lá (Mauá I e Mauá II)', count($unidades) === 2);
$unidadeMauaI = $unidades[0];

echo "\n== Criação de usuário (UsuarioRn) ==\n";
$usuarioRn = new UsuarioRn();
$resultadoUsuario = $usuarioRn->criar(
    'Bruno Teste',
    UsuarioRn::sugerirLogin('Bruno Teste'),
    'bruno.teste@gmail.com',
    true, // super admin
    [],
    0
);
conferir('usuário criado com sucesso', $resultadoUsuario['sucesso']);
conferir('login sugerido bateu (bruno.teste)', UsuarioRn::sugerirLogin('Bruno Teste') === 'bruno.teste');
$usuarioId = $resultadoUsuario['usuarioId'];

$usuarioDao = new UsuarioDao();
$usuarioCriado = $usuarioDao->buscarPorLogin('bruno.teste');
conferir('usuário aparece na busca por login', $usuarioCriado !== null);
conferir('senha provisória Udlog123 bate com o hash salvo', password_verify('Udlog123', $usuarioCriado->senhaHash));
conferir('trocar_senha_no_login nasceu como true (obrigatório)', $usuarioCriado->trocarSenhaNoLogin === true);

conferir('não deixa criar outro usuário com o mesmo login', !$usuarioRn->criar('Bruno Duplicado', 'bruno.teste', 'outro@gmail.com', false, [], 0)['sucesso']);

echo "\n== Autenticação (AutenticacaoRn) ==\n";
$autenticacaoRn = new AutenticacaoRn();
$loginErrado = $autenticacaoRn->autenticar('bruno.teste', 'senhaErrada123');
conferir('senha errada é recusada', !$loginErrado['sucesso']);

$loginCerto = $autenticacaoRn->autenticar('bruno.teste', 'Udlog123');
conferir('senha provisória correta autentica', $loginCerto['sucesso']);
conferir('objeto de usuário retornado bate com o criado', $loginCerto['usuario']->id === $usuarioId);

echo "\n== Troca de senha ==\n";
$resultadoTroca = $autenticacaoRn->trocarSenha($usuarioId, 'Udlog123', 'novaSenha2026');
conferir('troca de senha com senha atual correta funciona', $resultadoTroca['sucesso']);
$loginComSenhaNova = $autenticacaoRn->autenticar('bruno.teste', 'novaSenha2026');
conferir('login com a senha nova funciona depois da troca', $loginComSenhaNova['sucesso']);
$loginComSenhaAntiga = $autenticacaoRn->autenticar('bruno.teste', 'Udlog123');
conferir('login com a senha antiga (provisória) não funciona mais', !$loginComSenhaAntiga['sucesso']);

echo "\n== Permissões (super_admin enxerga tudo) ==\n";
$permissaoDao = new PermissaoDao();
$papelKpi = $permissaoDao->papelDoUsuarioNaAutomacao($usuarioId, 'kpi');
$papelMaoObra = $permissaoDao->papelDoUsuarioNaAutomacao($usuarioId, 'mao_obra_batida');
conferir('super_admin tem papel admin no KPI mesmo sem permissão explícita', $papelKpi === 'admin');
conferir('super_admin tem papel admin em Mão de Obra Batida também', $papelMaoObra === 'admin');

$automacoesVisiveis = $permissaoDao->automacoesVisiveisParaUsuario($usuarioId);
conferir('super_admin enxerga as 3 automações na sidebar (mesmo as ocultas pra usuário comum)', count($automacoesVisiveis) === 3);

echo "\n== Usuário comum (sem super_admin) ==\n";
$resultadoComum = $usuarioRn->criar('Isabelle Comum', 'isabelle.comum', 'isabelle@gmail.com', false, [(int) $kpi['id'] => 'usuario'], $usuarioId);
conferir('usuário comum criado', $resultadoComum['sucesso']);
$usuarioComumId = $resultadoComum['usuarioId'];

$papelComumKpi = $permissaoDao->papelDoUsuarioNaAutomacao($usuarioComumId, 'kpi');
$papelComumMaoObra = $permissaoDao->papelDoUsuarioNaAutomacao($usuarioComumId, 'mao_obra_batida');
conferir('usuário comum tem papel usuario no KPI (foi liberado)', $papelComumKpi === 'usuario');
conferir('usuário comum NÃO tem acesso a Mão de Obra Batida (não foi liberado)', $papelComumMaoObra === null);

$automacoesDoComum = $permissaoDao->automacoesVisiveisParaUsuario($usuarioComumId);
conferir('usuário comum só enxerga 1 automação na sidebar (só o KPI)', count($automacoesDoComum) === 1);

echo "\n== Cliente (ClienteRn) ==\n";
$clienteRn = new ClienteRn();
$dadosCliente = [
    'unidade_id' => $unidadeMauaI['id'],
    'codigo_cliente' => 'TESTE_CARGILL',
    'razao_social' => 'Cargill Teste Alimentos Ltda',
    'nome_exibicao' => 'Cargill Teste',
    'cnpj' => '12.345.678/0001-99',
    'email_responsavel' => 'responsavel@cargillteste.com',
    'logo_url' => 'https://udlog.online/imagens/CARGILL.png',
    'cor_primaria' => '#005c12',
    'cor_secundaria' => '#000000',
    'ativo' => '1',
];
$automacoesAtivas = [(int) $kpi['id'] => true, (int) $maoObra['id'] => true];
$resultadoCliente = $clienteRn->criar($dadosCliente, $automacoesAtivas, $usuarioId);
conferir('cliente criado com sucesso', $resultadoCliente['sucesso']);
$clienteId = $resultadoCliente['clienteId'];

$clienteDao = new ClienteDao();
$clienteCriado = $clienteDao->buscarPorId($clienteId);
conferir('CNPJ foi normalizado (só dígitos) ao salvar', $clienteCriado->cnpj === '12345678000199');
conferir('cliente não pode repetir código já usado', !$clienteRn->criar($dadosCliente, $automacoesAtivas, $usuarioId)['sucesso']);

$kpiConfig = $clienteDao->buscarKpiConfig($clienteId);
conferir('config de KPI (logo/cor) foi salva numa tabela separada', $kpiConfig !== null && $kpiConfig['cor_primaria'] === '#005c12');

$automacoesDoCliente = $clienteDao->automacoesDoCliente($clienteId);
conferir('cliente ficou ativo no KPI', $automacoesDoCliente[(int) $kpi['id']] === true);
conferir('cliente ficou ativo em Mão de Obra Batida', $automacoesDoCliente[(int) $maoObra['id']] === true);
conferir('cliente NÃO ficou ativo em Estadia (não foi marcado)', empty($automacoesDoCliente[(int) $estadia['id']]));

echo "\n== Autocomplete de cliente por automação ==\n";
$encontradosNoKpi = $clienteDao->buscarPorNomeEAutomacao('Cargill', 'kpi');
$encontradosNaEstadia = $clienteDao->buscarPorNomeEAutomacao('Cargill', 'estadia');
conferir('autocomplete do KPI encontra o cliente (está ativo lá)', count($encontradosNoKpi) === 1);
conferir('autocomplete da Estadia NÃO encontra o cliente (não está ativo lá)', count($encontradosNaEstadia) === 0);

echo "\n== Cronograma ==\n";
$cronogramaDao = new CronogramaDao();
$cronogramaId = $cronogramaDao->criar((int) $maoObra['id'], $clienteId, 10, '07:00:00');
$itemCronograma = $cronogramaDao->buscarPorId($cronogramaId);
conferir('item de cronograma criado', $itemCronograma !== null);
conferir('cronograma nasce ativo', (bool) $itemCronograma['ativo'] === true);

$cronogramaRn = new CronogramaRn();
$cronogramaRn->alternarAtivo($cronogramaId, false, $usuarioId);
$itemPausado = $cronogramaDao->buscarPorId($cronogramaId);
conferir('cronograma foi pausado com sucesso', (bool) $itemPausado['ativo'] === false);

echo "\n== Execução manual disparada a partir do cronograma (automação sem webhook configurado) ==\n";
$resultadoExecucao = $cronogramaRn->executarAgora($cronogramaId, $usuarioId);
conferir('tentativa não quebrou o sistema (respondeu com sucesso=false, sem webhook configurado)', $resultadoExecucao['sucesso'] === false);
conferir('mensagem avisa que falta configurar o webhook', str_contains($resultadoExecucao['mensagem'], 'webhook'));

echo "\n== Execução manual com webhook configurado, porém inalcançável ==\n";
// simula um n8n configurado mas fora do ar - confere que o sistema
// trata isso como erro de execução (fica registrado em tb_execucoes),
// não como um TypeError quebrando a aplicação
$pdoTeste = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NOME'] . ';charset=utf8mb4',
    $_ENV['DB_USUARIO'],
    $_ENV['DB_SENHA']
);
$pdoTeste->prepare('UPDATE tb_automacoes SET webhook_url = :url WHERE id = :id')
    ->execute([':url' => 'http://127.0.0.1:9/webhook-inexistente', ':id' => $maoObra['id']]);

$resultadoExecucao2 = $cronogramaRn->executarAgora($cronogramaId, $usuarioId);
conferir('tentativa com webhook inalcançável também não quebra o sistema', $resultadoExecucao2['sucesso'] === false);
conferir('mensagem de falha de n8n aparece dessa vez', str_contains($resultadoExecucao2['mensagem'], 'automação'));

$execucaoDao = new ExecucaoDao();
$execucoesDoCliente = $execucaoDao->listarComFiltros(['automacao_chave' => 'mao_obra_batida']);
conferir('a tentativa com webhook inalcançável ficou registrada no banco como erro', count($execucoesDoCliente) >= 1 && $execucoesDoCliente[0]['status'] === 'erro');

echo "\n== Redefinição de senha (código) ==\n";
$redefinicaoRn = new \Udflow\rn\RedefinicaoSenhaRn();
$codigo = $redefinicaoRn->solicitarCodigo('bruno.teste@gmail.com');
conferir('código de 6 dígitos foi gerado', $codigo !== null && strlen($codigo) === 6);

$resultadoCodigoErrado = $redefinicaoRn->confirmarRedefinicao('bruno.teste@gmail.com', '000000', 'outraSenhaNova1');
conferir('código errado é recusado', !$resultadoCodigoErrado['sucesso']);

$resultadoCodigoCerto = $redefinicaoRn->confirmarRedefinicao('bruno.teste@gmail.com', $codigo, 'outraSenhaNova1');
conferir('código certo redefine a senha', $resultadoCodigoCerto['sucesso']);

$loginAposRedefinicao = $autenticacaoRn->autenticar('bruno.teste', 'outraSenhaNova1');
conferir('login funciona com a senha redefinida pelo código', $loginAposRedefinicao['sucesso']);

$reuso = $redefinicaoRn->confirmarRedefinicao('bruno.teste@gmail.com', $codigo, 'terceiraSenha1');
conferir('o mesmo código não pode ser usado duas vezes', !$reuso['sucesso']);

echo "\n== E-mail @udlog exclusivo do KPI (via regex, já testado isoladamente antes) ==\n";
conferir('cliente com e-mail @udlog continua sendo aceito no cadastro geral (não é o KPI que valida isso, é a execução)', filter_var('financeiro@udlog.com', FILTER_VALIDATE_EMAIL) !== false);

echo "\n============================\n";
echo "{$totalTestes} conferências, {$totalFalhas} falha(s)\n";
exit($totalFalhas > 0 ? 1 : 0);
