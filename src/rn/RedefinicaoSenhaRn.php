<?php

namespace Udflow\rn;

use Udflow\dao\UsuarioDao;
use Udflow\dao\RedefinicaoSenhaDao;

/**
 * RedefinicaoSenhaRn
 *
 * O código de 6 dígitos nunca é salvo em texto puro no banco - a
 * gente guarda um hash dele (sha256 + pepper do .env). Como o
 * código já é curto, de uso único e expira em 15 minutos, não
 * precisa do custo do bcrypt aqui; o pepper é o que impede alguém
 * com acesso só ao banco (sem o .env) de forçar os 10^6 códigos
 * possíveis e descobrir qual bate.
 */
class RedefinicaoSenhaRn
{
    private UsuarioDao $usuarioDao;
    private RedefinicaoSenhaDao $redefinicaoDao;
    private const MINUTOS_VALIDADE = 15;

    public function __construct()
    {
        $this->usuarioDao = new UsuarioDao();
        $this->redefinicaoDao = new RedefinicaoSenhaDao();
    }

    /**
     * Gera o código, salva o hash e devolve o código puro (só pra
     * quem chamou poder mandar por e-mail - ele não fica guardado
     * em lugar nenhum depois disso).
     */
    public function solicitarCodigo(string $email): ?string
    {
        $usuario = $this->usuarioDao->buscarPorEmail($email);

        if ($usuario === null) {
            // não conta pro chamador que o e-mail não existe - assim
            // ninguém consegue usar essa tela pra descobrir quais
            // e-mails estão cadastrados no sistema
            return null;
        }

        $this->redefinicaoDao->invalidarPendentesDoUsuario($usuario->id);

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codigoHash = $this->gerarHashDoCodigo($codigo);

        $this->redefinicaoDao->criar($usuario->id, $codigoHash, self::MINUTOS_VALIDADE);

        return $codigo;
    }

    public function confirmarRedefinicao(string $email, string $codigoDigitado, string $senhaNova): array
    {
        $usuario = $this->usuarioDao->buscarPorEmail($email);

        if ($usuario === null) {
            return ['sucesso' => false, 'mensagem' => 'Código inválido ou expirado.'];
        }

        $pedido = $this->redefinicaoDao->buscarValidoPorUsuario($usuario->id);

        if ($pedido === null || !hash_equals($pedido['codigo_hash'], $this->gerarHashDoCodigo($codigoDigitado))) {
            return ['sucesso' => false, 'mensagem' => 'Código inválido ou expirado.'];
        }

        $erroSenha = AutenticacaoRn::regrasDeSenha($senhaNova);
        if ($erroSenha !== null) {
            return ['sucesso' => false, 'mensagem' => $erroSenha];
        }

        $this->usuarioDao->atualizarSenha($usuario->id, password_hash($senhaNova, PASSWORD_DEFAULT), false);
        $this->redefinicaoDao->marcarComoUsado((int) $pedido['id']);

        return ['sucesso' => true, 'mensagem' => null];
    }

    private function gerarHashDoCodigo(string $codigo): string
    {
        return hash_hmac('sha256', $codigo, $_ENV['APP_PEPPER']);
    }
}
