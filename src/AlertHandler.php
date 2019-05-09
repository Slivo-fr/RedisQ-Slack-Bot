<?php

namespace Killbot;

use Settings;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Twig_Environment;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;
use Twig_Loader_Filesystem;

class AlertHandler
{

    /**
     * @param $errorMessage
     * @param $file
     * @param $line
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public static function sendAlertMail($errorMessage, $file, $line)
    {

        $transport = (new Swift_SmtpTransport(Settings::$SMTP_SERVER, Settings::$SMTP_PORT, Settings::$SECURITY))
            ->setUsername(Settings::$SMTP_USER)
            ->setPassword(Settings::$SMTP_PASSWORD);

        if (Settings::$SECURITY == 'ssl') {
            $transport->setStreamOptions(
                array(
                    'ssl' => array(
                        'allow_self_signed' => true,
                        'verify_peer' => false,
                    ),
                )
            );
        }

        $mailer = new Swift_Mailer($transport);

        $message = (new Swift_Message('Killbot error !'))
            ->setFrom(Settings::$MAIL_SENDER)
            ->setTo(Settings::$MAIL_RECIPIENT)
            ->setBody(self::generateBody($errorMessage, $file, $line));


        if ($mailer->send($message)) {
            Logger::log('Alert mail sent');
        } else {
            Logger::log('Failed to send alert mail');
        }
    }

    /**
     * @param $message
     * @param $file
     * @param $line
     * @return string
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    protected static function generateBody($message, $file, $line)
    {
        $loader = new Twig_Loader_Filesystem('.');
        $twig = new Twig_Environment($loader);

        $body = $twig->render(
            'mail.html.twig',
            array(
                'message' => $message,
                'path' => $file,
                'line' => $line,
            )
        );

        return $body;
    }
}
