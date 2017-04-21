<?php

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
}