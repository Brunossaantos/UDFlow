<?php

namespace Udflow\rn;

use Udflow\dao\UsuarioDao;
use Udflow\dao\PermissaoDao;
use Udflow\dao\LogAdminDao;

/**
 * UsuarioRn
 *
 * Toda regra de criação/edição de usuário mora aqui. A senha
 * provisória é sempre "Udlog123" (decisão já tomada) - o Rn nunca
 * deixa passar em branco: todo usuário novo nasce com
 * trocar_senha_no_login = 1, então a troca é obrigatória no
 * primeiro acesso.
 */
class UsuarioRn
{
    private const SENHA_PROVISORIA = 'Udlog123';

    private UsuarioDao $usuarioDao;
    private PermissaoDao $permissaoDao;
    private LogAdminDao $logAdminDao;

    public function __construct()
    {
        $this->usuarioDao = new UsuarioDao();
        $this->permissaoDao = new PermissaoDao();
        $this->logAdminDao = new LogAdminDao();
    }

    /**
     * Gera a sugestão "bruno.carvalho" a partir de "Bruno Carvalho".
     * Tira acento, deixa minúsculo, junta primeiro e último nome com
     * ponto. Quem cadastra pode editar o resultado antes de salvar.
     * Estático de propósito: é só transformação de texto, não precisa
     * de banco pra isso.
     */
    public static function sugerirLogin(string $nomeCompleto): string
    {
        $semAcento = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nomeCompleto) ?: $nomeCompleto;
        $partes = preg_split('/\s+/', trim(mb_strtolower($semAcento)));
        $partes = array_filter($partes);

        if (empty($partes)) {
            return '';
        }

        $partes = array_values($partes);

        return count($partes) >= 2
            ? preg_replace('/[^a-z0-9.]/', '', $partes[0] . '.' . end($partes))
            : preg_replace('/[^a-z0-9.]/', '', $partes[0]);
    }

    /**
     * @param array<int,string> $permissoes automacao_id => papel ('usuario' | 'admin')
     * @return array{sucesso: bool, mensagem: ?string, usuarioId: ?int}
     */
    public function criar(
        string $nome,
        string $login,
        string $emailPessoal,
        bool $superAdmin,
        array $permissoes,
        int $executorId
    ): array {
        $nome = trim($nome);
        $login = trim(mb_strtolower($login));
        $emailPessoal = trim($emailPessoal);

        if ($nome === '' || $login === '') {
            return ['sucesso' => false, 'mensagem' => 'Preenche nome e nome de usuário.', 'usuarioId' => null];
        }
        if (!filter_var($emailPessoal, FILTER_VALIDATE_EMAIL)) {
            return ['sucesso' => false, 'mensagem' => 'Informe um e-mail pessoal válido.', 'usuarioId' => null];
        }
        if (!preg_match('/^[a-z0-9.]+$/', $login)) {
            return ['sucesso' => false, 'mensagem' => 'Nome de usuário só pode ter letra minúscula, número e ponto.', 'usuarioId' => null];
        }
        if ($this->usuarioDao->existeLoginOuEmail($login, $emailPessoal)) {
            return ['sucesso' => false, 'mensagem' => 'Já existe usuário com esse login ou e-mail.', 'usuarioId' => null];
        }

        $senhaHash = password_hash(self::SENHA_PROVISORIA, PASSWORD_DEFAULT);
        $usuarioId = $this->usuarioDao->criar($nome, $login, $emailPessoal, $senhaHash, $superAdmin);

        foreach ($permissoes as $automacaoId => $papel) {
            if ($papel === 'usuario' || $papel === 'admin') {
                $this->permissaoDao->definirPapel($usuarioId, (int) $automacaoId, $papel);
            }
        }

        $this->logAdminDao->registrar($executorId, 'usuario.criado', 'tb_usuarios', $usuarioId, "Usuário {$login} criado");

        return ['sucesso' => true, 'mensagem' => null, 'usuarioId' => $usuarioId];
    }

    public function alternarAtivo(int $usuarioId, bool $ativo, int $executorId): void
    {
        $this->usuarioDao->definirAtivo($usuarioId, $ativo);
        $acao = $ativo ? 'usuario.reativado' : 'usuario.desativado';
        $this->logAdminDao->registrar($executorId, $acao, 'tb_usuarios', $usuarioId, null);
    }

    public function atualizarPermissoes(int $usuarioId, array $permissoes, int $executorId): void
    {
        foreach ($permissoes as $automacaoId => $papel) {
            if ($papel === '' || $papel === 'sem_acesso') {
                $this->permissaoDao->removerAcesso($usuarioId, (int) $automacaoId);
            } else {
                $this->permissaoDao->definirPapel($usuarioId, (int) $automacaoId, $papel);
            }
        }

        $this->logAdminDao->registrar($executorId, 'usuario.permissoes_atualizadas', 'tb_usuarios', $usuarioId, null);
    }

    public function listarComPermissoes(): array
    {
        $usuarios = $this->usuarioDao->listarTodos();

        $resultado = [];
        foreach ($usuarios as $usuario) {
            $resultado[] = [
                'usuario' => $usuario,
                'permissoes' => $this->permissaoDao->permissoesPorUsuario($usuario->id),
            ];
        }

        return $resultado;
    }
}
