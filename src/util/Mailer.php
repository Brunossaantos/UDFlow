<?php

namespace Udflow\util;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Mailer
 *
 * Só sabe mandar e-mail via SMTP, usando os dados do .env. Mesmo
 * padrão que já é usado no TERMOS (PHPMailer) - centralizado aqui
 * pra qualquer parte do UDFlow reaproveitar. Hoje é só o código de
 * redefinição de senha, mas amanhã pode virar aviso de execução com
 * erro, por exemplo.
 */
class Mailer
{
    public static function enviar(string $destinatario, string $assunto, string $corpoHtml): bool
    {
        $mail = new PHPMailer(true);

        try {
           $mail->isSMTP();
            $mail->Timeout = 10; // não deixa a conexão travar a requisição inteira se a rede falhar
            $mail->Host = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USUARIO'];
            $mail->Password = $_ENV['SMTP_SENHA'];

            $porta = (int) ($_ENV['SMTP_PORTA'] ?? 587);
            $mail->Port = $porta;
            // porta 465 = SSL implícito (a conexão já nasce criptografada)
            // porta 587 = STARTTLS (conecta sem criptografia, depois faz upgrade)
            // detecta automaticamente pela porta configurada no .env, em vez de travar num tipo só
            $mail->SMTPSecure = $porta === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($_ENV['SMTP_REMETENTE'], 'UDFlow');
            $mail->addAddress($destinatario);

            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $corpoHtml;

            $mail->send();

            return true;
        } catch (PHPMailerException $e) {
            // não deixa o erro de SMTP vazar pra tela (pode conter host/usuário) -
            // só loga em arquivo e devolve false pra quem chamou decidir o que fazer
            error_log('Falha ao enviar e-mail para ' . $destinatario . ': ' . $mail->ErrorInfo);

            return false;
        }
    }
}