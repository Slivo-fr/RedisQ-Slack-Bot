# RedisQ-Slack-Bot
A simple PHP bot posting kills from Zkillboard to Slack.

You may watch kills related to corporations, alliances or even specific systems.

This bot use RedisQ (https://github.com/zKillboard/RedisQ) in order to provide quick information retrieval and short delay posting.

## Requirements
* Slack
* PHP 7 (with ext-curl)
* Composer

## Setup

Warning: You can only host this script once behind a given IP address due to RedisQ limitations

* Checkout the project 
* Run `composer install`
* Edit the first part of `Setting.php` to match your configuration.
> You must at least fill the `$SLACK_HOOK` variable and edit `$WATCHED_ENTITIES`.

* Setup a cronjob to run the `cron.php` file every 5 minutes (or another value matching your timeout settings).
 
 ## Mailing
 
 If you choose to use error mailing, you can check your configuration by sending a test mail running `php checkmail.php`.
 
 ## Disclaimer
 
 This project has been running fine for some time now, however it rely on zkillboard redisq service that may change at some point.
 
 Please report any weird behavior so I can fix it !
