<?php

/**
 * views/admin/clientes.php
 * Renderizado por AdminClienteController::tela()
 * Espera $clientesComDetalhes, $unidades, $automacoes
 */

use Udflow\util\Saida;

$paginaAtiva = 'admin-clientes';
$tituloPagina = 'Administração · Clientes';
require __DIR__ . '/../partials/cabecalho.php';
?>

<div class="flex items-center justify-between mb-1">
  <h1 class="font-display font-semibold text-xl">Clientes</h1>
  <button onclick="abrirDrawerCliente()" class="grad-flow text-[#04342C] font-semibold text-xs rounded-lg px-4 py-2 flex items-center gap-1.5">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
      <path d="M12 5v14M5 12h14" />
    </svg>Novo cliente
  </button>
</div>
<p class="text-tsecondary text-sm mb-5">Cadastro único, usado pelas 3 automações. Logo e cores de capa se aplicam somente ao KPI.</p>

<form method="GET" action="index.php" class="flex flex-wrap gap-3 mb-5">
  <input type="hidden" name="pagina" value="admin-clientes">
  <select name="unidade_id" onchange="this.form.submit()" class="bg-surface border border-bord rounded-lg px-3 py-2 text-xs glow-focus">
    <option value="">Todas as unidades</option>
    <?php foreach ($unidades as $u): ?>
      <option value="<?= (int) $u['id'] ?>" <?= (int) ($_GET['unidade_id'] ?? 0) === (int) $u['id'] ? 'selected' : '' ?>><?= Saida::e($u['nome']) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
  <?php foreach ($clientesComDetalhes as $item): $c = $item['cliente'];
    $kpi = $item['kpiConfig']; ?>
    <?php
    $corPrimaria = $kpi['cor_primaria'] ?? '#0B6FA4';
    $corSecundaria = $kpi['cor_secundaria'] ?? '#64748B';
    $automacoesJson = htmlspecialchars(json_encode($item['automacoesAtivas']), ENT_QUOTES, 'UTF-8');
    ?>
    <div class="bg-surface border border-bord rounded-xl overflow-hidden hover:border-flow/40 transition group">
      <div class="h-20 relative flex items-end p-3" style="background: linear-gradient(135deg, <?= Saida::e($corPrimaria) ?> 0%, <?= Saida::e($corSecundaria) ?> 100%);">
        <p class="text-white font-display font-semibold text-sm relative z-10"><?= Saida::e($c->nomeExibicao) ?></p>
        <span class="absolute top-3 right-3 text-[10px] bg-black/25 text-white px-2 py-0.5 rounded-full"><?= Saida::e($c->unidadeNome) ?></span>
      </div>
      <div class="p-3.5 flex items-center justify-end">
        <button
          onclick='abrirDrawerCliente(<?= json_encode([
                                        'id' => $c->id,
                                        'unidadeId' => $c->unidadeId,
                                        'codigo' => $c->codigoCliente,
                                        'codigoTalent' => $c->codigoTalent,
                                        'razaoSocial' => $c->razaoSocial,
                                        'nomeExibicao' => $c->nomeExibicao,
                                        'cnpj' => $c->cnpj,
                                        'emailResponsavel' => $c->emailResponsavel,
                                        'ativo' => $c->ativo,
                                        'logoUrl' => $kpi['logo_url'] ?? '',
                                        'corPrimaria' => $corPrimaria,
                                        'corSecundaria' => $corSecundaria,
                                        'automacoes' => $item['automacoesAtivas'],
                                      ]) ?>)'
          class="text-tsecondary hover:text-flow transition opacity-0 group-hover:opacity-100">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
          </svg>
        </button>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- DRAWER CLIENTE -->
<div id="drawer-cliente-overlay" class="hidden fixed inset-0 bg-black/50 z-50 justify-end">
  <div class="w-full max-w-md bg-elevated h-full border-l border-bord p-6 overflow-y-auto">
    <div class="flex items-center justify-between mb-6">
      <h3 id="drawer-titulo" class="font-display font-semibold text-base">Novo cliente</h3>
      <button onclick="fecharDrawerCliente()" class="text-tmuted hover:text-tprimary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M6 6l12 12M18 6L6 18" />
        </svg></button>
    </div>

    <div id="preview-capa" class="rounded-xl p-5 mb-6 relative overflow-hidden h-28 flex flex-col justify-end" style="background: linear-gradient(135deg, #0B6FA4 0%, #64748B 100%);">
      <p id="preview-nome" class="font-display font-semibold text-white text-lg relative z-10">Nome do cliente</p>
      <span class="absolute top-4 right-4 text-[10px] bg-white/15 text-white px-2 py-0.5 rounded-full">Capa · pré-visualização</span>
    </div>

    <form id="form-cliente" method="POST" action="index.php?pagina=admin-clientes-criar">
      <input type="hidden" name="csrf_token" value="<?= Saida::e($_SESSION['csrf_token'] ?? '') ?>">
      <input type="hidden" name="id" id="input-id">

      <div class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-tsecondary mb-1.5">Nome de exibição</label>
          <input id="input-nome-exibicao" type="text" name="nome_exibicao" required oninput="atualizarPreview()" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
        </div>
        <div>
          <label class="block text-xs font-medium text-tsecondary mb-1.5">Razão social</label>
          <input id="input-razao-social" type="text" name="razao_social" required class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-tsecondary mb-1.5">Código interno</label>
            <input id="input-codigo" type="text" name="codigo_cliente" required class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm font-mono glow-focus">
          </div>
          <div>
            <label class="block text-xs font-medium text-tsecondary mb-1.5">CNPJ</label>
            <input id="input-cnpj" type="text" name="cnpj" required placeholder="00.000.000/0000-00" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm font-mono glow-focus">
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-tsecondary mb-1.5">Código no Talent <span class="text-tmuted font-normal">· só Estadia</span></label>
          <input id="input-codigo-talent" type="text" name="codigo_talent" placeholder="Ex: ALLIANCE" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm font-mono glow-focus">
          <p class="text-[11px] text-tmuted mt-1">Como o cliente aparece no campo "depositante" da API do Talent. Sem isso, a Estadia não dispara pra esse cliente.</p>
        </div>
        <div>
          <label class="block text-xs font-medium text-tsecondary mb-1.5">Unidade</label>
          <select id="input-unidade" name="unidade_id" required class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
            <?php foreach ($unidades as $u): ?>
              <option value="<?= (int) $u['id'] ?>"><?= Saida::e($u['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-tsecondary mb-1.5">E-mail do responsável</label>
          <input id="input-email-responsavel" type="email" name="email_responsavel" placeholder="usado no disparo automático (Cronograma)" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus">
        </div>
        <div>
          <label class="block text-xs font-medium text-tsecondary mb-1.5">URL do logo</label>
          <input id="input-logo" type="text" name="logo_url" oninput="atualizarPreview()" class="w-full bg-surface border border-bord rounded-lg px-3 py-2.5 text-sm font-mono glow-focus">
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-tsecondary mb-1.5">Cor primária</label>
            <div class="flex items-center gap-2 bg-surface border border-bord rounded-lg px-3 py-2">
              <input id="input-cor1" type="color" name="cor_primaria" oninput="atualizarPreview(); sincronizarHex('cor1')" class="w-7 h-7 rounded-md cursor-pointer">
              <input id="texto-cor1" type="text" class="flex-1 bg-transparent text-xs font-mono text-tsecondary border-0 outline-none" maxlength="7" oninput="sincronizarCor('input-cor1', 'texto-cor1'); atualizarPreview()" placeholder="#000000">
            </div>
          </div>
          <div>
            <label class="block text-xs font-medium text-tsecondary mb-1.5">Cor secundária</label>
            <div class="flex items-center gap-2 bg-surface border border-bord rounded-lg px-3 py-2">
              <input id="input-cor2" type="color" name="cor_secundaria" oninput="atualizarPreview(); sincronizarHex('cor2')" class="w-7 h-7 rounded-md cursor-pointer">
              <input id="texto-cor2" type="text" class="flex-1 bg-transparent text-xs font-mono text-tsecondary border-0 outline-none" maxlength="7" oninput="sincronizarCor('input-cor2', 'texto-cor2'); atualizarPreview()" placeholder="#000000">
            </div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-tsecondary mb-2">Participa das automações</label>
          <div class="border border-bord rounded-lg divide-y divide-bord">
            <?php foreach ($automacoes as $automacao): ?>
              <label class="flex items-center justify-between px-4 py-3 text-sm cursor-pointer">
                <?= Saida::e($automacao['nome']) ?>
                <input type="checkbox" name="automacoes[]" value="<?= (int) $automacao['id'] ?>" id="chk-automacao-<?= (int) $automacao['id'] ?>" class="rounded border-bord">
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <label class="flex items-center justify-between bg-surface border border-bord rounded-lg px-4 py-3">
          <span class="text-sm">Cliente ativo</span>
          <input type="checkbox" id="input-ativo" name="ativo" value="1" checked class="rounded border-bord">
        </label>
      </div>

      <div class="flex gap-3 mt-8">
        <button type="button" onclick="fecharDrawerCliente()" class="flex-1 border border-bord rounded-lg py-2.5 text-sm font-medium hover:bg-surface transition">Cancelar</button>
        <button type="submit" class="flex-1 grad-flow text-[#04342C] font-semibold rounded-lg py-2.5 text-sm hover:opacity-90 transition">Salvar cliente</button>
      </div>
    </form>
  </div>
</div>

<script>
  function abrirDrawerCliente(dados) {
    const form = document.getElementById('form-cliente');
    document.querySelectorAll('input[name="automacoes[]"]').forEach(c => c.checked = false);

    if (dados && dados.id) {
      document.getElementById('drawer-titulo').textContent = 'Editar cliente';
      form.action = 'index.php?pagina=admin-clientes-atualizar';
      document.getElementById('input-id').value = dados.id;
      document.getElementById('input-nome-exibicao').value = dados.nomeExibicao;
      document.getElementById('input-razao-social').value = dados.razaoSocial;
      document.getElementById('input-codigo').value = dados.codigo;
      document.getElementById('input-codigo-talent').value = dados.codigoTalent || '';
      document.getElementById('input-cnpj').value = dados.cnpj;
      document.getElementById('input-unidade').value = dados.unidadeId;
      document.getElementById('input-email-responsavel').value = dados.emailResponsavel || '';
      document.getElementById('input-logo').value = dados.logoUrl || '';
      document.getElementById('input-cor1').value = dados.corPrimaria || '#0B6FA4';
      document.getElementById('input-cor2').value = dados.corSecundaria || '#64748B';
      document.getElementById('input-ativo').checked = !!dados.ativo;
      Object.keys(dados.automacoes || {}).forEach(id => {
        if (dados.automacoes[id]) {
          const chk = document.getElementById('chk-automacao-' + id);
          if (chk) chk.checked = true;
        }
      });
    } else {
      document.getElementById('drawer-titulo').textContent = 'Novo cliente';
      form.action = 'index.php?pagina=admin-clientes-criar';
      form.reset();
      document.getElementById('input-id').value = '';
      document.getElementById('input-cor1').value = '#0B6FA4';
      document.getElementById('input-cor2').value = '#64748B';
    }

    atualizarPreview();
    document.getElementById('drawer-cliente-overlay').classList.remove('hidden');
    document.getElementById('drawer-cliente-overlay').classList.add('flex');
  }

  function fecharDrawerCliente() {
    document.getElementById('drawer-cliente-overlay').classList.add('hidden');
    document.getElementById('drawer-cliente-overlay').classList.remove('flex');
  }

  function sincronizarHex(tipo) {
    const inputCor = document.getElementById('input-cor' + (tipo === 'cor1' ? '1' : '2'));
    const textoCor = document.getElementById('texto-cor' + (tipo === 'cor1' ? '1' : '2'));
    textoCor.value = inputCor.value.toUpperCase();
  }

  function sincronizarCor(inputId, textoId) {
    const input = document.getElementById(inputId);
    const texto = document.getElementById(textoId);
    const valor = texto.value.trim();

    // Valida formato hexadecimal
    if (valor.match(/^#[0-9A-F]{6}$/i)) {
      input.value = valor.toLowerCase();
    }
  }

  function atualizarPreview() {
    const nome = document.getElementById('input-nome-exibicao').value || 'Nome do cliente';
    const c1 = document.getElementById('input-cor1').value;
    const c2 = document.getElementById('input-cor2').value;
    document.getElementById('preview-nome').textContent = nome;
    document.getElementById('preview-capa').style.background = `linear-gradient(135deg, ${c1} 0%, ${c2} 100%)`;
    document.getElementById('texto-cor1').value = c1.toUpperCase();
    document.getElementById('texto-cor2').value = c2.toUpperCase();
  }
</script>

<?php require __DIR__ . '/../partials/rodape.php'; ?>