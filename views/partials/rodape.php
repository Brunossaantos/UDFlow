<?php

/**
 * partials/rodape.php
 *
 * Fecha as divs abertas no cabecalho.php e carrega o JS
 * compartilhado (toast e o helper de fetch com CSRF).
 */
?>
</div>
</main>
</div>

<div id="toast" class="hidden fixed bottom-6 right-6 bg-elevated border border-bord rounded-xl px-4 py-3 shadow-2xl z-[60] items-center gap-3 max-w-xs">
  <div id="toast-icone" class="w-7 h-7 rounded-full bg-flow/15 flex items-center justify-center shrink-0"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1FD8C4" stroke-width="2.5">
      <path d="M20 6L9 17l-5-5" />
    </svg></div>
  <p id="toast-msg" class="text-sm text-tprimary"></p>
</div>

<script>
  const CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;

  function toast(msg, tipo = 'sucesso') {
    const t = document.getElementById('toast');
    const icone = document.getElementById('toast-icone');
    document.getElementById('toast-msg').textContent = msg;

    if (tipo === 'erro') {
      icone.className = 'w-7 h-7 rounded-full bg-danger/15 flex items-center justify-center shrink-0';
      icone.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#F87171" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>';
    } else {
      icone.className = 'w-7 h-7 rounded-full bg-flow/15 flex items-center justify-center shrink-0';
      icone.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1FD8C4" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>';
    }

    t.classList.remove('hidden');
    t.classList.add('flex');
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => {
      t.classList.add('hidden');
      t.classList.remove('flex');
    }, 3500);
  }

  /** fetch() já mandando o token CSRF certo, pros endpoints que respondem em JSON */
  async function enviarComCsrf(url, dadosExtras = {}) {
    const corpo = new URLSearchParams({
      csrf_token: CSRF_TOKEN,
      ...dadosExtras
    });
    const resposta = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: corpo,
    });
    return resposta.json();
  }
</script>
<!-- LEO - CHAT COM IA -->
<div id="chat-ia-balao" class="hidden fixed bottom-24 right-6 z-50">
  <div class="relative bg-elevated border border-bord rounded-2xl rounded-br-sm px-4 py-3 shadow-2xl max-w-[220px] leo-balao-pulando">
    <button onclick="fecharBalaoChatIA()" class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-surface border border-bord flex items-center justify-center text-tmuted hover:text-tprimary">
      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
        <path d="M6 6l12 12M18 6L6 18" />
      </svg>
    </button>
    <p class="text-sm cursor-pointer" onclick="abrirChatIA()">
      <span class="font-semibold">Oi! Sou o Leo 👋</span><br>
      <span class="text-tsecondary text-xs">Seu assistente virtual</span>
    </p>
  </div>
</div>

<div id="chat-ia-bolha" onclick="abrirChatIA()" class="fixed bottom-6 right-6 w-14 h-14 rounded-full shadow-2xl overflow-hidden cursor-pointer z-50 hover:scale-105 transition bg-white border border-bord">
  <img src="https://udlog.online/imagens/Leo.png" alt="Leo" class="w-full h-full object-cover">
</div>

<div id="chat-ia-painel" class="hidden fixed bottom-24 right-6 w-96 max-w-[calc(100vw-3rem)] h-[520px] bg-elevated border border-bord rounded-2xl shadow-2xl z-50 flex flex-col overflow-hidden">
  <div class="flex items-center gap-2.5 px-4 h-14 border-b border-bord shrink-0 bg-surface">
    <div class="w-8 h-8 rounded-full overflow-hidden shrink-0 bg-white border border-bord">
      <img src="https://udlog.online/imagens/Leo.png" alt="Leo" class="w-full h-full object-cover">
    </div>
    <div class="min-w-0 flex-1">
      <p class="text-sm font-semibold">Leo</p>
      <p class="text-[10px] text-tmuted">Assistente virtual</p>
    </div>
    <button onclick="alternarChatIA()" class="text-tmuted hover:text-tprimary"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M6 6l12 12M18 6L6 18" />
      </svg></button>
  </div>

  <div id="chat-ia-mensagens" class="flex-1 overflow-y-auto p-4 space-y-3">
    <div class="bg-surface border border-bord rounded-xl rounded-tl-sm px-3.5 py-2.5 text-sm max-w-[85%]">
      Olá! Eu sou o Leo 🦁, assistente inteligente do UDFlow. Como posso ajudar?
    </div>
  </div>

  <form id="chat-ia-form" class="border-t border-bord p-3 flex gap-2 shrink-0">
    <input id="chat-ia-input" type="text" placeholder="Escreve sua dúvida..." autocomplete="off" class="flex-1 bg-surface border border-bord rounded-lg px-3 py-2 text-sm glow-focus">
    <button type="submit" class="grad-flow text-[#04342C] rounded-lg px-3.5 py-2 shrink-0">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
        <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" />
      </svg>
    </button>
  </form>
</div>

<style>
  @keyframes leo-balao-entrada {
    0% {
      transform: translateY(20px) scale(0.85);
      opacity: 0;
    }

    60% {
      transform: translateY(-6px) scale(1.03);
      opacity: 1;
    }

    100% {
      transform: translateY(0) scale(1);
    }
  }

  @keyframes leo-balao-pular {

    0%,
    100% {
      transform: translateY(0);
    }

    50% {
      transform: translateY(-8px);
    }
  }

  .leo-balao-pulando {
    animation: leo-balao-entrada 0.5s ease-out, leo-balao-pular 1.8s ease-in-out 0.5s infinite;
  }
</style>

<script>
  function abrirChatIA() {
    document.getElementById('chat-ia-painel').classList.remove('hidden');
    document.getElementById('chat-ia-bolha').classList.add('hidden');
    fecharBalaoChatIA();
  }

  function alternarChatIA() {
    document.getElementById('chat-ia-painel').classList.add('hidden');
    document.getElementById('chat-ia-bolha').classList.remove('hidden');
  }

    function fecharBalaoChatIA() {
    document.getElementById('chat-ia-balao').classList.add('hidden');
  }

  // mostra o balão pulando 1.5s depois de carregar a página, e some
  // sozinho depois de 10s se ninguém clicar nele - aparece de novo
  // toda vez que a página recarrega ou troca de tela
  setTimeout(() => {
    document.getElementById('chat-ia-balao').classList.remove('hidden');
    setTimeout(fecharBalaoChatIA, 10000);
  }, 1500);

  function formatarRespostaIA(texto) {
    let seguro = texto.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    seguro = seguro.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

    const linhas = seguro.split('\n');
    let html = '';
    let dentroDeLista = false;
    let tipoLista = null;

    for (const linha of linhas) {
      const itemNumerado = linha.match(/^\d+\.\s+(.*)/);
      const itemComTraco = linha.match(/^[-*]\s+(.*)/);

      if (itemNumerado || itemComTraco) {
        const novoTipo = itemNumerado ? 'ol' : 'ul';
        if (!dentroDeLista) {
          html += `<${novoTipo} class="pl-4 ${novoTipo === 'ol' ? 'list-decimal' : 'list-disc'} space-y-0.5">`;
          dentroDeLista = true;
          tipoLista = novoTipo;
        } else if (tipoLista !== novoTipo) {
          html += `</${tipoLista}><${novoTipo} class="pl-4 ${novoTipo === 'ol' ? 'list-decimal' : 'list-disc'} space-y-0.5">`;
          tipoLista = novoTipo;
        }
        html += `<li>${(itemNumerado || itemComTraco)[1]}</li>`;
      } else {
        if (dentroDeLista) {
          html += `</${tipoLista}>`;
          dentroDeLista = false;
          tipoLista = null;
        }
        html += linha + '<br>';
      }
    }
    if (dentroDeLista) html += `</${tipoLista}>`;

    return html;
  }

  document.getElementById('chat-ia-form').addEventListener('submit', async (evento) => {
    evento.preventDefault();
    const campo = document.getElementById('chat-ia-input');
    const mensagem = campo.value.trim();
    if (!mensagem) return;

    const lista = document.getElementById('chat-ia-mensagens');
    lista.insertAdjacentHTML('beforeend', `
      <div class="bg-flow/10 border border-flow/20 rounded-xl rounded-tr-sm px-3.5 py-2.5 text-sm max-w-[85%] ml-auto">${mensagem.replace(/</g, '&lt;')}</div>
    `);
    campo.value = '';
    lista.scrollTop = lista.scrollHeight;

    const indicador = document.createElement('div');
    indicador.className = 'bg-surface border border-bord rounded-xl rounded-tl-sm px-3.5 py-2.5 text-sm max-w-[85%] text-tmuted';
    indicador.textContent = 'digitando...';
    lista.appendChild(indicador);
    lista.scrollTop = lista.scrollHeight;

    try {
      const resultado = await enviarComCsrf('index.php?pagina=chat-enviar', {
        mensagem
      });
      indicador.remove();
      if (resultado.sucesso) {
        lista.insertAdjacentHTML('beforeend', `
          <div class="bg-surface border border-bord rounded-xl rounded-tl-sm px-3.5 py-2.5 text-sm max-w-[85%]">${formatarRespostaIA(resultado.resposta)}</div>
        `);
      } else {
        lista.insertAdjacentHTML('beforeend', `
          <div class="bg-danger/10 border border-danger/20 rounded-xl rounded-tl-sm px-3.5 py-2.5 text-sm max-w-[85%] text-danger">${resultado.mensagem}</div>
        `);
      }
    } catch (erro) {
      indicador.remove();
      lista.insertAdjacentHTML('beforeend', `<div class="text-danger text-xs">Falha de conexão, tenta de novo.</div>`);
    }
    lista.scrollTop = lista.scrollHeight;
  });
</script>

</body>

</html>