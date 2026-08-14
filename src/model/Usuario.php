<?php

namespace Udflow\model;

/**
 * Usuario
 *
 * Representa uma linha da tb_usuarios. Só guarda dado, não faz
 * consulta nem regra nenhuma - isso é trabalho do Dao e do Rn.
 */
class Usuario
{
    public ?int $id = null;
    public string $nome = '';
    public string $usuario = '';
    public string $email = '';
    public string $senhaHash = '';
    public bool $superAdmin = false;
    public bool $trocarSenhaNoLogin = true;
    public bool $ativo = true;

    public static function apartirDoBanco(array $linha): self
    {
        $usuario = new self();
        $usuario->id = (int) $linha['id'];
        $usuario->nome = $linha['nome'];
        $usuario->usuario = $linha['usuario'];
        $usuario->email = $linha['email'];
        $usuario->senhaHash = $linha['senha_hash'];
        $usuario->superAdmin = (bool) $linha['super_admin'];
        $usuario->trocarSenhaNoLogin = (bool) $linha['trocar_senha_no_login'];
        $usuario->ativo = (bool) $linha['ativo'];

        return $usuario;
    }

    /** O que fica salvo na sessão depois do login - só o essencial, nunca a senha */
    public function paraSessao(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'usuario' => $this->usuario,
            'super_admin' => $this->superAdmin,
            'trocar_senha_no_login' => $this->trocarSenhaNoLogin,
        ];
    }
}
