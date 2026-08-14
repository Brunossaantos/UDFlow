<?php

namespace Udflow\rn;

use Udflow\dao\UsuarioDao;
use Udflow\util\ProtecaoForcaBruta;

/**
 * AutenticacaoRn
 *
 * Regra de negócio do login. O Controller não fala com o Dao
 * diretamente pra login nenhuma vez - sempre passa por aqui, que é
 * quem decide se a senha bate, se a conta tá bloqueada por
 * tentativas erradas, etc.
 */
class AutenticacaoRn
{
    private UsuarioDao $usuarioDao;

    public function __construct()
    {
        $this->usuarioDao = new UsuarioDao();
    }

    /**
     * @return array{sucesso: bool, mensagem: ?string, usuario: ?\Udflow\model\Usuario}
     */
    public function autenticar(string $usuario, string $senha): array
    {
        if (!ProtecaoForcaBruta::podeTentar()) {
            $segundos = ProtecaoForcaBruta::segundosRestantesDeBloqueio();
            return [
                'sucesso' => false,
                'mensagem' => "Muitas tentativas erradas. Tente de novo em {$segundos} segundos.",
                'usuario' => null,
            ];
        }

        $usuarioEncontrado = $this->usuarioDao->buscarPorLogin($usuario);

        // mesma mensagem tanto pra "usuário não existe" quanto pra "senha errada" -
        // não faz sentido dar dica pra quem tá tentando adivinhar login válido
        $mensagemGenerica = 'Usuário ou senha incorretos.';

        if ($usuarioEncontrado === null || !password_verify($senha, $usuarioEncontrado->senhaHash)) {
            ProtecaoForcaBruta::registrarFalha();
            return ['sucesso' => false, 'mensagem' => $mensagemGenerica, 'usuario' => null];
        }

        ProtecaoForcaBruta::registrarSucesso();

        return ['sucesso' => true, 'mensagem' => null, 'usuario' => $usuarioEncontrado];
    }

    public function iniciarSessao(\Udflow\model\Usuario $usuario): void
    {
        // troca o ID da sessão depois do login - impede um ataque de
        // fixação de sessão (alguém forçar a vítima a usar um ID de
        // sessão que o atacante já conhece)
        session_regenerate_id(true);

        $_SESSION['usuario'] = $usuario->paraSessao();
    }

    public function encerrarSessao(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public function trocarSenha(int $usuarioId, string $senhaAtual, string $senhaNova): array
    {
        $usuario = $this->usuarioDao->buscarPorId($usuarioId);

        if ($usuario === null || !password_verify($senhaAtual, $usuario->senhaHash)) {
            return ['sucesso' => false, 'mensagem' => 'Senha atual incorreta.'];
        }

        $erroValidacao = $this->validarForcaDaSenha($senhaNova);
        if ($erroValidacao !== null) {
            return ['sucesso' => false, 'mensagem' => $erroValidacao];
        }

        $this->usuarioDao->atualizarSenha($usuarioId, password_hash($senhaNova, PASSWORD_DEFAULT), false);

        return ['sucesso' => true, 'mensagem' => null];
    }

    public function validarForcaDaSenha(string $senha): ?string
    {
        return self::regrasDeSenha($senha);
    }

    /** Estático de propósito: não depende de banco, só confere o formato da senha */
    public static function regrasDeSenha(string $senha): ?string
    {
        if (mb_strlen($senha) < 8) {
            return 'A senha precisa ter no mínimo 8 caracteres.';
        }
        if (!preg_match('/[a-zA-Z]/', $senha) || !preg_match('/[0-9]/', $senha)) {
            return 'A senha precisa ter letras e números.';
        }

        return null;
    }
}
