<?php

namespace Killbot;

use Exception;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Twig_Environment;

class AlertHandler
{

    public static function sendAlertMail(Exception $e) {

        $transport = (new Swift_SmtpTransport(Settings::$SMTP_SERVER, Settings::$SMTP_PORT, Settings::$SECURITY))
            ->setUsername(Settings::$SMTP_USER)
            ->setPassword(Settings::$SMTP_PASSWORD);

        if (Settings::$SECURITY == 'ssl') {
            $transport->setStreamOptions(
                array(
                    'ssl' => array(
                        'allow_self_signed' => true,
                        'verify_peer' => false
                    )
                )
            );
        }

        $mailer = new Swift_Mailer($transport);

        $message = (new Swift_Message('Killbot error !'))
            ->setFrom(Settings::$MAIL_SENDER)
            ->setTo(Settings::$MAIL_RECIPIENT)
            ->setBody(self::generateBody($e));


        if ($mailer->send($message)) {
            echo "Alert mail sent\n";
        } else {
            echo "Failed to send alert mail\n";
        }
    }
    protected static function generateBody(Exception $e)
    {
        $loader = new \Twig_Loader_Filesystem('.');
        $twig = new Twig_Environment($loader);

        $body = $twig->render(
            'mail.html.twig',
            array(
                'message' => $e->getMessage(),
                'path' => $e->getFile(),
                'line' => $e->getLine(),
            )
        );

        return $body;
    }
}
