<?php
/**
 * bootstrap.php
 *
 * Primeira coisa que roda em toda requisição. Carrega o autoload do
 * Composer, lê o .env, ajusta timezone/erro e sobe a sessão já com
 * as configurações de cookie seguras. Se isso não rodar antes, nada
 * mais no sistema deve rodar.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// --- Variáveis de ambiente ---
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NOME', 'DB_USUARIO', 'APP_PEPPER']);

date_default_timezone_set('America/Sao_Paulo');

$ambienteProducao = ($_ENV['APP_AMBIENTE'] ?? 'producao') === 'producao';

\Udflow\util\LogSistema::registrarManipuladoresGlobais($ambienteProducao);

if ($ambienteProducao) {
    // em produção a gente não mostra stack trace pro usuário final,
    // só loga em arquivo mesmo
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../storage/logs/php-erros.log');
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// --- Sessão ---
// Precisa configurar os parâmetros do cookie ANTES do session_start(),
// senão eles não têm efeito nenhum.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,        // expira quando fecha o navegador (decisão: sem "continuar conectado")
        'path'     => '/',
        'domain'   => '',
        'secure'   => $ambienteProducao, // só manda cookie por HTTPS em produção
        'httponly' => true,     // JS não consegue ler o cookie de sessão
        'samesite' => 'Lax',    // segura contra CSRF vindo de outro site sem quebrar links normais
    ]);
    session_name('udflow_sessao');
    session_start();
}

// timeout por inatividade: 30 minutos sem nenhuma requisição derruba a sessão
$tempoLimiteInatividade = 30 * 60;
if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > $tempoLimiteInatividade) {
    $_SESSION = [];
    session_destroy();
    session_start();
}
$_SESSION['ultimo_acesso'] = time();
