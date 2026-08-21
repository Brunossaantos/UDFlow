const fragmento = "const entrada = $input.first().json;\nconst dados = typeof entrada.dados === 'string'\n ?
JSON.parse(entrada.dados)\n : entrada.dados;\n\nif (!dados?.meta) {\n throw new Error('A consulta PostgreSQL não
retornou o objeto dados.meta.');\n}\n\n/*\n * REGRA GLOBAL DE FECHAMENTO DOS KPIs\n * Um mês só aparece a partir do dia
1 do mês seguinte.\n * Ex.: durante agosto, agosto fica \"-\"; em 01/setembro, agosto é liberado.\n */\nconst
agoraSaoPauloPartes = new Intl.DateTimeFormat('en-CA', {\n timeZone: 'America/Sao_Paulo',\n year: 'numeric',\n month:
'2-digit',\n day: '2-digit',\n}).formatToParts(new Date());\n\nconst agoraSaoPaulo = Object.fromEntries(\n
agoraSaoPauloPartes\n .filter((parte) => parte.type !== 'literal')\n .map((parte) => [parte.type,
Number(parte.value)])\n);\n\nconst anoAtualSaoPaulo = agoraSaoPaulo.year;\nconst mesAtualSaoPaulo =
agoraSaoPaulo.month;\nconst anoRelatorio = Number(dados.meta.ano);\n\nfunction mesLiberado(mes) {\n const numeroMes =
Number(mes);\n\n if (!Number.isInteger(numeroMes) || numeroMes < 1 || numeroMes> 12) {\n return true;\n }\n\n if
    (anoRelatorio < anoAtualSaoPaulo) return true;\n if (anoRelatorio> anoAtualSaoPaulo) return false;\n\n return
        numeroMes < mesAtualSaoPaulo;\n}\n\nconst CHAVES_ESTRUTURA_MENSAL=new Set([\n 'mes' ,\n 'unidade'
            ,\n 'disponivel' ,\n 'mesCompleto' ,\n 'origemValor' ,\n]);\n\nfunction ocultarMesesNaoFechados(valor) {\n
            if (Array.isArray(valor)) {\n return valor.map((item)=> ocultarMesesNaoFechados(item));\n }\n\n if (!valor
            || typeof valor !== 'object') {\n return valor;\n }\n\n const resultado = {};\n\n for (const [chave,
            conteudo] of Object.entries(valor)) {\n resultado[chave] = ocultarMesesNaoFechados(conteudo);\n }\n\n const
            numeroMes = Number(valor.mes);\n\n if (\n Object.prototype.hasOwnProperty.call(valor, 'mes') &&\n
            Number.isInteger(numeroMes) &&\n numeroMes >= 1 &&\n numeroMes <= 12 &&\n !mesLiberado(numeroMes)\n ) {\n
                for (const chave of Object.keys(resultado)) {\n if (CHAVES_ESTRUTURA_MENSAL.has(chave)) continue;\n\n
                const conteudoOriginal=valor[chave];\n\n if (\n conteudoOriginal !==null &&\n typeof
                conteudoOriginal==='object' \n ) {\n continue;\n }\n\n resultado[chave]=null;\n }\n\n if ('disponivel'
                in resultado) resultado.disponivel=false;\n if ('mesCompleto' in resultado)
                resultado.mesCompleto=false;\n }\n\n return resultado;\n}\n\nconst
                dadosFiltrados=ocultarMesesNaoFechados(dados);\n\nfor (const chave of Object.keys(dados)) {\n delete
                dados[chave];\n}\nObject.assign(dados, dadosFiltrados);\n\nconst MESES=[\n 'JANEIRO' , 'FEVEREIRO'
                , 'MARÇO' , 'ABRIL' , 'MAIO' , 'JUNHO' ,\n 'JULHO' , 'AGOSTO' , 'SETEMBRO' , 'OUTUBRO' , 'NOVEMBRO'
                , 'DEZEMBRO' ,\n];\nconst MESES_CURTOS=[\n 'JAN' , 'FEV' , 'MAR' , 'ABR' , 'MAI' , 'JUN' ,\n 'JUL'
                , 'AGO' , 'SET' , 'OUT' , 'NOV' , 'DEZ' ,\n];\nconst PALETA=[\n '#0B6FA4' , '#F97316' , '#14B8A6'
                , '#7C3AED' ,\n '#EAB308' , '#EC4899' , '#64748B' , '#22C55E' ,\n];\n\nconst
                temaCapaRecebido=dados.meta.temaCapa || {};\n\nconst corPrimaria=\n temaCapaRecebido.primaria
                || '#0B6FA4' ;\n\nconst corSecundaria=\n temaCapaRecebido.secundaria || '#64748B' ;\n\nconst
                armazenagemPorServico=\n dados.armazenagem_por_servico ?? {\n modelo: 'QUINZENAL' ,\n series: [],\n
                quantidade_servicos: 0,\n quantidade_unidades: 0,\n };\n\nconst usarArmazenagemPorServico=\n
                armazenagemPorServico.modelo==='POR_SERVICO' &&\n Array.isArray(armazenagemPorServico.series) &&\n
                armazenagemPorServico.series.length> 0;\n\nconst recebimentoPorServico =\n dados.recebimento_por_servico
                ?? {\n modelo: 'QUINZENAL',\n series: [],\n quantidade_servicos: 0,\n quantidade_unidades: 0,\n
                };\n\nconst usarRecebimentoPorServico =\n recebimentoPorServico.modelo === 'POR_SERVICO' &&\n
                Array.isArray(recebimentoPorServico.series) &&\n recebimentoPorServico.series.length > 0;\n\nconst
                expedicaoPorServico =\n dados.expedicao_por_servico ?? {\n modelo: 'QUINZENAL',\n series: [],\n
                quantidade_servicos: 0,\n quantidade_unidades: 0,\n };\n\nconst usarExpedicaoPorServico =\n
                expedicaoPorServico.modelo === 'POR_SERVICO' &&\n Array.isArray(expedicaoPorServico.series) &&\n
                expedicaoPorServico.series.length > 0;\n\nconst altoTiete =\n";
                return [{ json: { ...$json, __kpi_code: ($json.__kpi_code || '') + fragmento } }];