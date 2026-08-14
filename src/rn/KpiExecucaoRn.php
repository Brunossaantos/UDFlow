<?php

namespace Udflow\rn;

/**
 * KpiExecucaoRn
 *
 * Mesma regra de ExecucaoRn, só que o KPI, por enquanto, só pode
 * mandar relatório pra e-mail @udlog. Quando isso mudar (ou quando
 * as outras automações também precisarem da mesma trava), é só
 * herdar daqui ou promover essa validação de volta pra classe mãe.
 */
class KpiExecucaoRn extends ExecucaoRn
{
    protected function validarEmailDestino(string $email): ?string
    {
        $erroBasico = parent::validarEmailDestino($email);
        if ($erroBasico !== null) {
            return $erroBasico;
        }

        if (!preg_match('/@udlog\.[a-z.]+$/i', $email)) {
            return 'O e-mail de destino precisa ser @udlog.';
        }

        return null;
    }
}
