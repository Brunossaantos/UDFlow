<?php
/**
 * views/admin/usuarios.php
 * Renderizado por AdminUsuarioController::tela()
 * Espera $usuarios (cada item: ['usuario' => Usuario, 'permissoes' => [...]]) e $automacoes
 */

use Udflow\util\Saida;

$paginaAtiva = 'admin-usuarios';
$tituloPagina = 'Administração · Usuários';
require __DIR__ . '/../partials/cabecalho.php';
?>

<div class="flex items-center justify-between mb-1">
  <h1 class="font-display font-semibold text-xl">Usuários e permissões</h1>
  <button onclick="document.getElementById('modal-novo-usuario').classList.remove('hidden'); document.getElementById('modal-novo-usuario').classList.add('flex')"
    class="grad-flow text-[#04342C] font-semibold text-xs rounded-lg px-4 py-2 flex items-center gap-1.5">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"/></svg>Novo usuário
  </button>
</div>
<p class="text-tsecondary text-sm mb-6">Defina quem acessa cada automação e com qual papel. Exclusão apenas desativa o acesso.</p>

<div class="bg-surface border border-bord rounded-2xl overflow-hidden">
  <table class="w-full text-sm">
    <thead>
      <tr class="border-b border-bord text-left text-tmuted text-xs">
        <th class="font-medium px-5 py-3">Usuário</th>
        <th class="font-medium px-5 py-3">Login</th>
        <th class="font-medium px-5 py-3">Automações</th>
        <th class="font-medium px-5 py-3">Status</th>
        <th class="font-medium px-5 py-3 text-right">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($usuarios as $linha): $u = $linha['usuario']; ?>
        <tr class="border-b border-bordsoft last:border-0 hover:bg-elevated/40 transition">
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-2.5">
              <div class="w-7 h-7 rounded-full grad-flow flex items-center justify-center text-[10px] font-bold text-[#04342C]"><?= Saida::e(mb_strtoupper(mb_substr($u->nome, 0, 1))) ?></div>
              <span class="font-medium"><?= Saida::e($u->nome) ?></span>
              <?php if ($u->superAdmin): ?><span class="text-[9px] bg-amber/15 text-amber px-1.5 py-0.5 rounded font-bold">SUPER</span><?php endif; ?>
            </div>
          </td>
          <td class="px-5 py-3.5 text-tsecondary font-mono text-xs"><?= Saida::e($u->usuario) ?></td>
          <td class="px-5 py-3.5">
            <?php foreach ($linha['permissoes'] as $p): if ($p['papel'] === null) continue; ?>
              <span class="text-[10px] bg-elevated border border-bord px-2 py-0.5 rounded-full mr-1"><?= Saida::e($p['nome']) ?> · <?= $p['papel'] === 'admin' ? 'Admin' : 'Usuário' ?></span>
            <?php endforeach; ?>
          </td>
          <td class="px-5 py-3.5"><span class="text-xs font-medium <?= $u->ativo ? 'text-success' : 'text-tmuted' ?>"><?= $u->ativo ? 'Ativo' : 'Desativado' ?></span></td>
          <td class="px-5 py-3.5 text-right">
            <button onclick="alternarAtivo(<?= (int) $u->id ?>, <?= $u->ativo ? 'false' : 'true' ?>)" class="text-xs text-tsecondary hover:text-flow transition"><?= $u->ativo ? 'Desativar' : 'Reativar' ?></button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- MODAL NOVO USUÁRIO -->
<div id="modal-novo-usuario" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
  <div class="w-full max-w-lg bg-elevated border border-bord rounded-2xl p-6 max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between mb-5">
      <h3 class="font-display font-semibold text-base">Novo usuário</h3>
      <button onclick="document.getElementById('modal-novo-usuario').classList.add('hidden'); document.getElementById('modal-novo-usuario').classList.remove('flex')" class="text-tmuted hover:text-tprimary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
    </div>

    <form method="POST" action="index.php?pagina=admin-usuarios-criar">
      <input type="hidden" name="csrf_token" value="<?= Saida::e($_SESSION['csrf_token'] ?? '') ?>">

      <div class="mb-4">
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Nome completo</label>
        <input id="campo-nome-usuario" type="text" name="nome" required placeholder="Bruno Carvalho" oninput="sugerirLogin()" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
      </div>
      <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-xs font-medium text-tsecondary mb-1.5">Nome de usuário</label>
          <input id="campo-login-usuario" type="text" name="usuario" required placeholder="bruno.carvalho" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm font-mono glow-focus">
          <p class="text-[11px] text-tmuted mt-1">Gerado do nome, pode editar</p>
        </div>
        <div>
          <label class="block text-xs font-medium text-tsecondary mb-1.5">E-mail pessoal</label>
          <input type="email" name="email" required placeholder="brunossaantos@gmail.com" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
          <p class="text-[11px] text-tmuted mt-1">Usado pra redefinir a senha</p>
        </div>
      </div>

      <div class="mb-4">
        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" name="super_admin" value="1" class="rounded border-bord">
          Super administrador (acesso e controle total do sistema)
        </label>
      </div>

      <div class="mb-5">
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Senha provisória</label>
        <div class="flex items-center gap-2 bg-surface border border-bord rounded-lg px-3 py-2.5">
          <span class="text-sm font-mono text-tprimary flex-1">Udlog123</span>
          <span class="text-[10px] bg-amber/10 text-amber px-2 py-0.5 rounded-full font-medium">Trocar no 1º acesso</span>
        </div>
        <p class="text-[11px] text-tmuted mt-1">Padrão pra todo novo cadastro - a troca é obrigatória no primeiro login.</p>
      </div>

      <div class="mb-5">
        <label class="block text-xs font-medium text-tsecondary mb-2">Permissões por automação</label>
        <div class="border border-bord rounded-lg divide-y divide-bord">
          <?php foreach ($automacoes as $automacao): ?>
            <div class="flex items-center justify-between px-4 py-3">
              <span class="text-sm"><?= Saida::e($automacao['nome']) ?></span>
              <select name="permissoes[<?= (int) $automacao['id'] ?>]" class="bg-surface border border-bord rounded-md px-2.5 py-1.5 text-xs glow-focus">
                <option value="">Sem acesso</option>
                <option value="usuario">Usuário</option>
                <option value="admin">Administrador</option>
              </select>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="flex gap-3">
        <button type="button" onclick="document.getElementById('modal-novo-usuario').classList.add('hidden'); document.getElementById('modal-novo-usuario').classList.remove('flex')" class="flex-1 border border-bord rounded-lg py-2.5 text-sm font-medium hover:bg-surface transition">Cancelar</button>
        <button type="submit" class="flex-1 grad-flow text-[#04342C] font-semibold rounded-lg py-2.5 text-sm hover:opacity-90 transition">Criar usuário</button>
      </div>
    </form>
  </div>
</div>

<script>
  function sugerirLogin() {
    const nome = document.getElementById('campo-nome-usuario').value.trim();
    const semAcento = nome.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const partes = semAcento.split(/\s+/).filter(Boolean);
    if (partes.length === 0) { document.getElementById('campo-login-usuario').value = ''; return; }
    const sugestao = partes.length >= 2 ? `${partes[0]}.${partes[partes.length - 1]}` : partes[0];
    document.getElementById('campo-login-usuario').value = sugestao.replace(/[^a-z0-9.]/g, '');
  }

  async function alternarAtivo(usuarioId, ativo) {
    const resultado = await enviarComCsrf('index.php?pagina=admin-usuarios-ativo', { usuario_id: usuarioId, ativo: ativo ? '1' : '0' });
    if (resultado.sucesso) {
      toast(ativo ? 'Usuário reativado' : 'Usuário desativado');
      setTimeout(() => location.reload(), 700);
    } else {
      toast(resultado.mensagem || 'Não foi possível atualizar.', 'erro');
    }
  }
</script>

<?php require __DIR__ . '/../partials/rodape.php'; ?>
