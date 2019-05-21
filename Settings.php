<?php

class Settings
{

    /*******************************************************************************************************************
     * Basic configuration
     * Should be enough to get your bot running.
     * Dont forget to setup a cron job executing cron.php every $MAX_RUN_TIME + a small safety offset
     * Running each 5 minutes should be fine with the default $MAX_RUN_TIME
     ******************************************************************************************************************/

    // Entity ids you want to display killmails for.
    // You may have different hooks used for watching different entities, just duplicate 'default'.
    public static $watchingConfigurations = [
        'default' => [
            'SLACK_HOOK' => 'https://hooks.slack.com/services/ABC/ABC/ABC',
            'WATCHED_ENTITIES' => [
                'corporations' => [
                    123,
                ],
                'alliances' => [
                    1234,
                    12345,
                ],
                'systems' => [
                    30000142,
                ],
            ],
        ],
    ];

    // 4 minutes default max run time
    public static $MAX_RUN_TIME = 4 * 60;

    public static $KILL_HISTORY_MAX_LENGTH = 50;

    /*******************************************************************************************************************
     * Mail settings
     * Allow script to send mail alert when something goes wrong
     ******************************************************************************************************************/

    // Enable sending mails
    public static $SEND_MAIL = false;

    // Server configuration
    public static $SMTP_SERVER = 'smtp.example.com';
    public static $SMTP_PORT = 465;
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
    public static $HTTP_HEADER = 'RedisQ-Slack-Bot https://github.com/Slivo-fr/RedisQ-Slack-Bot';

    // URL to redisQ
    public static $REDISQ_URL = 'https://redisq.zkillboard.com/listen.php?ttw=1';

    // Enable debugging behaviors
    public static $DEBUG = false;

    // Define running environment, use 'DEV' or 'PROD'
    public static $ENV = 'PROD';

    // Logs path
    public static $LOG_PATH = 'var/logs/';

    // Pending kills path
    public static $UNPROCESSED_PATH = 'var/pending/';

    // ESI base URL
    public static $ESI_URL = 'https://esi.evetech.net/';
}
