# RedisQ-Slack-Bot

A simple PHP bot posting kills from Zkillboard to Slack.

This bot use RedisQ (https://github.com/zKillboard/RedisQ) in order to provide quick information retrieval and short delay posting.

#### Setup

Clone this project and edit the first part of `Setting.php` to match your configuration.
Use a cronjob to run the `cron.php` file every 5 minutes (or another value matching your timeout settings).
 
 #### Disclaimer
 
 This project is in early alpha state and may crash or malfunction. I'll try to fix issues occurring on my side, please report if you get some specific case that don't work. 