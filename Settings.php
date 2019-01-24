<?php

namespace Killbot;

class Settings {

    /*******************************************************************************************************************
     * Basic configuration
     * Should be enough to get your bot running.
     * Dont forget to setup a cronjob executing cron.php every $MAX_RUN_TIME + a small offset
     ******************************************************************************************************************/

    // Slack webhook URL
    public static $SLACK_HOOK   = 'https://hooks.slack.com/services/ABC/DEF/GHI';

    // 4 minutes default max run time
    public static $MAX_RUN_TIME = 4 * 60;

    // Entities you want to display killmails for
    public static $WATCHED_ENTITIES = [
        'corporations' => [
            123456,
            1324567
        ],
        'alliances' => [
            13245678
        ],
        'systems' => [
            30003504
        ]
    ];

    /*******************************************************************************************************************
     * Mail settings
     * Allow script to send mail alert when something goes wrong
     ******************************************************************************************************************/

    // Enable sending mails
    public static $SEND_MAIL = true;

    // Server configuration
    public static $SMTP_SERVER = 'smtp.example.com';
    public static $SMTP_PORT = '465';
    public static $SMTP_USER = 'user@example.com';
    public static $SMTP_PASSWORD = 'my_password';

    // Use null or ssl if required
    public static $SECURITY = 'ssl';

    // Mail addresses
    public static $MAIL_RECIPIENT = 'recipient@example.com';
    public static $MAIL_SENDER = 'sender@example.com';

    /*******************************************************************************************************************
     * Advanced configuration
     * Do not edit unless you know what you are doing
     ******************************************************************************************************************/

    // HTTP header sent with each bot request
    public static $HTTP_HEADER  = 'RedisQ-Slack-Bot https://github.com/Slivo-fr/RedisQ-Slack-Bot';

    // URL to redisQ
    public static $REDISQ_URL = 'https://redisq.zkillboard.com/listen.php?ttw=1';

    // Enable debugging behaviors
    public static $DEBUG = false;

    // Folder name for json logs
    public static $KILL_LOG_FOLDER = 'logs';

    // ESI base URL
    public static $ESI_URL = 'https://esi.evetech.net/';
}
