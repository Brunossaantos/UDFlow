<?php

namespace Udflow\util;

use Udflow\dao\LogSistemaDao;
use Throwable;

/**
 * LogSistema
 *
 * Ponto único pra registrar qualquer erro/bug do sistema, seja
 * capturado automaticamente (set_error_handler, set_exception_handler,
 * register_shutdown_function - ver registrarManipuladoresGlobais) ou
 * registrado manualmente num catch de negócio (webhook, e-mail, chat).
 *
 * Nunca deixa uma falha AO logar derrubar a requisição: se gravar no
 * banco falhar, cai pro error_log de arquivo (já configurado no
 * bootstrap) em vez de propagar a exceção.
 */
class LogSistema
{
    private static bool $manipuladoresRegistrados = false;

    public static function registrar(string $nivel, string $mensagem, ?string $arquivo = null, ?int $linha = null, ?array $contexto = null): void
    {
        try {
            (new LogSistemaDao())->registrar($nivel, $mensagem, $arquivo, $linha, $contexto);
        } catch (Throwable $e) {
            error_log("[LogSistema] Falha ao gravar log no banco ({$e->getMessage()}) - mensagem original: [{$nivel}] {$mensagem}" . ($arquivo ? " em {$arquivo}:{$linha}" : ''));
        }
    }

    public static function registrarExcecao(Throwable $e, string $nivel = 'exception'): void
    {
        self::registrar(
            $nivel,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            ['trace' => $e->getTraceAsString(), 'pagina' => $_GET['pagina'] ?? null]
        );
    }

    /**
     * Registra os 3 manipuladores globais que cobrem qualquer PHP
     * error/warning, exception não capturada e erro fatal, tanto nas
     * requisições web (bootstrap.php) quanto no cron (bootstrap-cli.php).
     */
    public static function registrarManipuladoresGlobais(bool $ambienteProducao): void
    {
        if (self::$manipuladoresRegistrados) {
            return;
        }
        self::$manipuladoresRegistrados = true;

        // warnings, notices, deprecated: loga mas não interrompe a
        // execução (return false mantém o comportamento nativo do PHP,
        // que já está configurado no bootstrap - display/log em arquivo)
        set_error_handler(function (int $codigo, string $mensagem, string $arquivo, int $linha): bool {
            self::registrar('warning', $mensagem, $arquivo, $linha, ['codigo_php' => $codigo, 'pagina' => $_GET['pagina'] ?? null]);
            return false;
        });

        // exception não capturada por ninguém: loga e mostra uma
        // resposta genérica pro usuário final (sem stack trace) em
        // produção, ou a mensagem real fora de produção
        set_exception_handler(function (Throwable $e) use ($ambienteProducao): void {
            self::registrarExcecao($e, 'exception');

            http_response_code(500);
            if ($ambienteProducao) {
                echo 'Ocorreu um erro inesperado. Já foi registrado e a equipe vai verificar.';
            } else {
                echo 'Erro não tratado: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine();
            }
        });

        // fatais (E_ERROR, E_PARSE, E_COMPILE_ERROR) não passam pelos
        // dois handlers acima - só chegam aqui, no shutdown
        register_shutdown_function(function (): void {
            $erro = error_get_last();
            if ($erro === null) {
                return;
            }

            $fataisQueImportam = [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR];
            if (!in_array($erro['type'], $fataisQueImportam, true)) {
                return;
            }

            self::registrar('fatal', $erro['message'], $erro['file'], $erro['line'], ['pagina' => $_GET['pagina'] ?? null]);
        });
    }
}
