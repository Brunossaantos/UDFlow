<?php
/**
 * partials/tela-execucao.php
 *
 * As 3 automações (KPI, Programação semanal, Estadia) têm exatamente
 * a mesma tela: busca de cliente, e-mail, botão de disparo e a
 * lista de "minhas solicitações". Em vez de repetir esse HTML 3
 * vezes, cada view (kpi.php, mao-obra.php, estadia.php) só define
 * as variáveis abaixo e inclui esse partial.
 *
 * Espera:
 *   $chaveRota            -> 'kpi' | 'mao-obra' | 'estadia' (bate com as rotas em rotas.php)
 *   $rotuloBotao          -> texto do botão de disparo
 *   $avisoEmailUdlog      -> bool, mostra o aviso "somente @udlog"
 *   $avisoProximoDisparo  -> string|null, texto do próximo disparo automático (null = automação sem agendamento)
 *   $minhasExecucoes      -> array vindo de ExecucaoDao::listarDoUsuario()
 *   $mostrarColunaOrigem  -> bool, mostra a coluna Manual/Automático na tabela
 */

use Udflow\util\Saida;

$statusLabel = [
    'pendente' => ['Pendente', 'bg-amber/10 text-amber'],
    'processando' => ['Processando', 'bg-info/10 text-info'],
    'concluido' => ['Concluído', 'bg-success/10 text-success'],
    'erro' => ['Erro', 'bg-danger/10 text-danger'],
];
?>

<div class="flex items-center gap-3 mb-1">
  <h1 class="font-display font-semibold text-xl"><?= Saida::e($tituloPagina) ?></h1>
</div>
<p class="text-tsecondary text-sm mb-2">Busque o cliente, informe o e-mail de destino e dispare.</p>
<?php if ($avisoProximoDisparo !== null): ?>
  <p class="text-tmuted text-xs mb-6 flex items-center gap-1.5">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
    <?= Saida::e($avisoProximoDisparo) ?>
  </p>
<?php else: ?>
  <div class="mb-6"></div>
<?php endif; ?>

<div class="bg-surface border border-bord rounded-2xl p-6 mb-8">
  <div class="grid grid-cols-1 md:grid-cols-[1.4fr_1fr_auto] gap-4 items-end">
    <div class="relative">
      <label class="block text-xs font-medium text-tsecondary mb-1.5">Cliente</label>
      <div class="relative">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="absolute left-3 top-1/2 -translate-y-1/2 text-tmuted"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <input id="campo-cliente-busca" type="text" autocomplete="off" placeholder="Digite o nome do cliente..." class="w-full bg-elevated border border-bord rounded-lg pl-9 pr-3 py-2.5 text-sm glow-focus transition">
      </div>
      <div id="lista-clientes" class="hidden absolute z-30 mt-1.5 w-full bg-elevated border border-bord rounded-xl overflow-hidden shadow-2xl max-h-64 overflow-y-auto"></div>
      <input type="hidden" id="campo-cliente-id">
    </div>
    <div>
      <label class="block text-xs font-medium text-tsecondary mb-1.5">E-mail de destino</label>
      <input id="campo-email" type="email" placeholder="<?= $avisoEmailUdlog ? 'nome@udlog.com' : 'nome@empresa.com' ?>" class="w-full bg-elevated border border-bord rounded-lg px-3 py-2.5 text-sm glow-focus transition">
      <?php if ($avisoEmailUdlog): ?>
        <!-- <p class="text-[11px] text-tmuted mt-1">Somente e-mails @udlog</p> -->
      <?php endif; ?>
    </div>
    <button id="botao-disparar" class="grad-flow text-[#04342C] font-semibold text-sm rounded-lg px-5 py-2.5 whitespace-nowrap hover:opacity-90 transition h-[42px]"><?= Saida::e($rotuloBotao) ?></button>
  </div>
  <div id="chip-selecionado" class="hidden mt-3 inline-flex items-center gap-2 bg-flow/10 border border-flow/20 rounded-full pl-1.5 pr-3 py-1"></div>
</div>

<div class="flex items-center justify-between mb-3">
  <p class="text-xs font-semibold tracking-widest text-tmuted">MINHAS SOLICITAÇÕES</p>
  <button onclick="location.reload()" class="text-xs text-tsecondary hover:text-flow flex items-center gap-1.5 transition">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.5 9a9 9 0 0114.5-4.5L23 9M1 15l5.5 4.5A9 9 0 0020.5 15"/></svg>Atualizar
  </button>
</div>

<div class="bg-surface border border-bord rounded-2xl overflow-hidden">
  <table class="w-full text-sm">
    <thead>
      <tr class="border-b border-bord text-left text-tmuted text-xs">
        <th class="font-medium px-5 py-3">Cliente</th>
        <th class="font-medium px-5 py-3">E-mail</th>
        <?php if ($mostrarColunaOrigem): ?><th class="font-medium px-5 py-3">Origem</th><?php endif; ?>
        <th class="font-medium px-5 py-3">Status</th>
        <th class="font-medium px-5 py-3">Data</th>
      </tr>
    </thead>
    <tbody id="corpo-execucoes">
      <?php if (empty($minhasExecucoes)): ?>
        <tr><td colspan="5" class="px-5 py-10 text-center text-tmuted text-sm">Nenhuma solicitação ainda. Dispare a primeira acima.</td></tr>
      <?php else: ?>
        <?php foreach ($minhasExecucoes as $execucao): ?>
          <?php [$rotuloStatus, $corStatus] = $statusLabel[$execucao['status']] ?? ['—', 'bg-tmuted/10 text-tmuted']; ?>
          <tr class="border-b border-bordsoft last:border-0 hover:bg-elevated/40 transition">
            <td class="px-5 py-3.5 font-medium"><?= Saida::e($execucao['cliente_nome']) ?></td>
            <td class="px-5 py-3.5 text-tsecondary font-mono text-xs"><?= Saida::e($execucao['email_destino']) ?></td>
            <?php if ($mostrarColunaOrigem): ?>
              <td class="px-5 py-3.5 text-xs text-tsecondary"><?= $execucao['origem'] === 'automatico' ? 'Automático' : 'Manual' ?></td>
            <?php endif; ?>
            <td class="px-5 py-3.5"><span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full <?= $corStatus ?>"><?= Saida::e($rotuloStatus) ?></span></td>
            <td class="px-5 py-3.5 text-tmuted text-xs font-mono"><?= Saida::e(date('d/m H:i', strtotime($execucao['criado_em']))) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
(function () {
  const rotaBase = <?= json_encode($chaveRota) ?>;
  const mostrarColunaOrigem = <?= json_encode((bool) $mostrarColunaOrigem) ?>;
  const corpoExecucoes = document.getElementById('corpo-execucoes');
  const campoBusca = document.getElementById('campo-cliente-busca');
  const listaClientes = document.getElementById('lista-clientes');
  const campoClienteId = document.getElementById('campo-cliente-id');
  const chipSelecionado = document.getElementById('chip-selecionado');
  const campoEmail = document.getElementById('campo-email');
  const botaoDisparar = document.getElementById('botao-disparar');
  let temporizadorBusca = null;

  campoBusca.addEventListener('input', () => {
    clearTimeout(temporizadorBusca);
    const termo = campoBusca.value.trim();
    campoClienteId.value = '';
    chipSelecionado.classList.add('hidden');

    if (termo.length < 2) {
      listaClientes.classList.add('hidden');
      return;
    }

    temporizadorBusca = setTimeout(async () => {
      const resposta = await fetch(`index.php?pagina=${rotaBase}-clientes&termo=${encodeURIComponent(termo)}`);
      const dados = await resposta.json();
      renderizarLista(dados.clientes || []);
    }, 220);
  });

  function renderizarLista(clientes) {
    if (clientes.length === 0) {
      listaClientes.innerHTML = '<div class="px-4 py-3 text-xs text-tmuted">Nenhum cliente encontrado</div>';
    } else {
      listaClientes.innerHTML = clientes.map(c => `
        <button type="button" data-id="${c.id}" data-nome="${c.nome.replace(/"/g, '&quot;')}"
          class="item-cliente w-full flex items-center gap-3 px-4 py-2.5 hover:bg-surface transition text-left">
          <div class="w-7 h-7 rounded-md shrink-0 bg-surface border border-bord flex items-center justify-center text-[10px] font-bold text-tsecondary">${c.nome.slice(0, 2).toUpperCase()}</div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">${c.nome}</p>           
          </div>
          <span class="text-[10px] bg-surface border border-bord px-2 py-0.5 rounded-full text-tsecondary shrink-0">${c.unidade}</span>
        </button>`).join('');
    }
    listaClientes.classList.remove('hidden');

    listaClientes.querySelectorAll('.item-cliente').forEach(botao => {
      botao.addEventListener('click', () => {
        campoClienteId.value = botao.dataset.id;
        campoBusca.value = botao.dataset.nome;
        listaClientes.classList.add('hidden');
        chipSelecionado.classList.remove('hidden');
        chipSelecionado.innerHTML = `<span class="text-xs font-medium text-flow">${botao.dataset.nome} selecionado</span>`;
      });
    });
  }

  document.addEventListener('click', (evento) => {
    if (!evento.target.closest('#campo-cliente-busca') && !evento.target.closest('#lista-clientes')) {
      listaClientes.classList.add('hidden');
    }
  });

  botaoDisparar.addEventListener('click', async () => {
    const clienteId = campoClienteId.value;
    const email = campoEmail.value.trim();

    if (!clienteId) { toast('Selecione um cliente antes de continuar', 'erro'); return; }
    if (!email) { toast('Informe o e-mail de destino', 'erro'); return; }

    botaoDisparar.disabled = true;
    botaoDisparar.classList.add('opacity-60');

    try {
      const resultado = await enviarComCsrf(`index.php?pagina=${rotaBase}-enviar`, { cliente_id: clienteId, email });
      if (resultado.sucesso) {
        toast('Solicitação enviada com sucesso');
        setTimeout(() => location.reload(), 900);
      } else {
        toast(resultado.mensagem || 'Não foi possível disparar agora.', 'erro');
      }
    } catch (erro) {
      toast('Falha de conexão, tenta de novo.', 'erro');
    } finally {
      botaoDisparar.disabled = false;
      botaoDisparar.classList.remove('opacity-60');
    }
  });

  // ------------------------------------------------------------------
  // Atualização automática de "minhas solicitações" - pergunta pro
  // servidor a cada 8s como estão as execuções do usuário e redesenha
  // só a tabela, sem recarregar a página. Pausa quando a aba não está
  // visível pra não gastar requisição à toa.
  // ------------------------------------------------------------------
  const statusLabel = {
    pendente: ['Pendente', 'bg-amber/10 text-amber'],
    processando: ['Processando', 'bg-info/10 text-info'],
    concluido: ['Concluído', 'bg-success/10 text-success'],
    erro: ['Erro', 'bg-danger/10 text-danger'],
  };

  function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
  }

  function formatarData(dataIso) {
    const data = new Date(dataIso.replace(' ', 'T'));
    if (isNaN(data.getTime())) return '';
    const doisDigitos = (n) => String(n).padStart(2, '0');
    return `${doisDigitos(data.getDate())}/${doisDigitos(data.getMonth() + 1)} ${doisDigitos(data.getHours())}:${doisDigitos(data.getMinutes())}`;
  }

  function renderizarExecucoes(execucoes) {
    if (!corpoExecucoes) return;

    if (execucoes.length === 0) {
      const colunas = mostrarColunaOrigem ? 5 : 4;
      corpoExecucoes.innerHTML = `<tr><td colspan="${colunas}" class="px-5 py-10 text-center text-tmuted text-sm">Nenhuma solicitação ainda. Dispare a primeira acima.</td></tr>`;
      return;
    }

    corpoExecucoes.innerHTML = execucoes.map((e) => {
      const [rotulo, cor] = statusLabel[e.status] || ['—', 'bg-tmuted/10 text-tmuted'];
      const colunaOrigem = mostrarColunaOrigem
        ? `<td class="px-5 py-3.5 text-xs text-tsecondary">${e.origem === 'automatico' ? 'Automático' : 'Manual'}</td>`
        : '';

      return `
        <tr class="border-b border-bordsoft last:border-0 hover:bg-elevated/40 transition">
          <td class="px-5 py-3.5 font-medium">${escaparHtml(e.cliente_nome)}</td>
          <td class="px-5 py-3.5 text-tsecondary font-mono text-xs">${escaparHtml(e.email_destino)}</td>
          ${colunaOrigem}
          <td class="px-5 py-3.5"><span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full ${cor}">${rotulo}</span></td>
          <td class="px-5 py-3.5 text-tmuted text-xs font-mono">${formatarData(e.criado_em)}</td>
        </tr>`;
    }).join('');
  }

  async function atualizarExecucoes() {
    if (document.hidden) return;

    try {
      const resposta = await fetch(`index.php?pagina=${rotaBase}-status`);
      if (!resposta.ok) return;
      const dados = await resposta.json();
      renderizarExecucoes(dados.execucoes || []);
    } catch (erro) {
      // falha de rede pontual não precisa incomodar o usuário -
      // a próxima tentativa em 8s resolve sozinha
    }
  }

  setInterval(atualizarExecucoes, 8000);
})();
</script>
