<?php

namespace Udflow\util;

/**
 * ProtecaoForcaBruta
 *
 * Controle simples de tentativas de login, guardado na própria
 * sessão do navegador que está tentando. Não precisa de tabela no
 * banco pra isso - com poucos usuários internos, isso aqui já
 * resolve o problema de alguém ficar chutando senha no loop.
 *
 * Depois de 5 tentativas erradas, tranca por 1 minuto. Se errar nas
 * próximas 5 (com o bloqueio já vencido), tranca de novo por mais
 * tempo (2, depois 4, até um teto de 15 min). É simples e barato,
 * não é infalível - mas cobre o caso comum de força bruta manual.
 */
class ProtecaoForcaBruta
{
    private const CHAVE = 'protecao_login';
    private const LIMITE_TENTATIVAS = 5;
    private const BLOQUEIO_INICIAL_SEGUNDOS = 60;
    private const BLOQUEIO_MAXIMO_SEGUNDOS = 900; // 15 min

    public static function podeTentar(): bool
    {
        $estado = $_SESSION[self::CHAVE] ?? null;

        if ($estado === null || empty($estado['bloqueado_ate'])) {
            return true;
        }

        return time() >= $estado['bloqueado_ate'];
    }

    public static function segundosRestantesDeBloqueio(): int
    {
        $estado = $_SESSION[self::CHAVE] ?? null;

        if ($estado === null || empty($estado['bloqueado_ate'])) {
            return 0;
        }

        return max(0, $estado['bloqueado_ate'] - time());
    }

    public static function registrarFalha(): void
    {
        $estado = $_SESSION[self::CHAVE] ?? ['tentativas' => 0, 'nivel_bloqueio' => 0, 'bloqueado_ate' => 0];
        $estado['tentativas']++;

        if ($estado['tentativas'] >= self::LIMITE_TENTATIVAS) {
            $duracao = min(
                self::BLOQUEIO_INICIAL_SEGUNDOS * (2 ** $estado['nivel_bloqueio']),
                self::BLOQUEIO_MAXIMO_SEGUNDOS
            );
            $estado['bloqueado_ate'] = time() + $duracao;
            $estado['nivel_bloqueio']++;
            $estado['tentativas'] = 0;
        }

        $_SESSION[self::CHAVE] = $estado;
    }

    public static function registrarSucesso(): void
    {
        unset($_SESSION[self::CHAVE]);
    }
}
