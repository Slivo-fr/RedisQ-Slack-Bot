# RedisQ-Slack-Bot
A simple PHP bot posting kills from Zkillboard to Slack.

You may watch kills related to corporations, alliances or even specific systems.

This bot use RedisQ (https://github.com/zKillboard/RedisQ) in order to provide quick information retrieval and short delay posting.

## Requirements
* Slack
* PHP (with ext-curl)

## Setup

Checkout this project and edit the first part of `Setting.php` to match your configuration.

You must at least fill the `$SLACK_HOOK` variable and edit `$WATCHED_ENTITIES`.

Use a cronjob to run the `cron.php` file every 5 minutes (or another value matching your timeout settings).
 
 ## Disclaimer
 
 This project has been running fine for some time now, however it rely on zkillboard redisq service that may change at some point.
 
 Please report any weird behavior so I can fix it !
