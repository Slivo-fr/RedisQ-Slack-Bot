<?php

namespace Killbot;

use stdClass;

class Killbot
{

    protected $knownShips = array();
    protected $knownCharacters = array();
    protected $knownCorporations = array();

    /**
     * Runs the bot
     */
    public function run()
    {

        $isFeedEmpty = false;
        $timeout = time() + Settings::$MAX_RUN_TIME;

        while (!$isFeedEmpty && time() < $timeout) {

            $isFeedEmpty = !$this->processKill();
        }
    }

    /**
     * Fetch and process one kill from RedisQ
     * Return false if the queue is empty, true otherwise
     */
    private function processKill()
    {

        $rawOutput = $this->curlRequest(Settings::$REDISQ_URL);

        $data = json_decode($rawOutput);

        if (!isset($data->{'package'}) || $data->{'package'} == null) {
            return false;
        }

        foreach (Settings::$watchingConfigurations as $watchingConfiguration) {

            $slackHook = $watchingConfiguration['SLACK_HOOK'];
            $watchedEntities = $watchingConfiguration['WATCHED_ENTITIES'];

            // Only process kill that match settings entities
            if ($this->isWatchedKill($data, $watchedEntities)) {

                if (Settings::$DEBUG) {
                    $this->storeKillJson($data->{'package'}->{'killID'}, $rawOutput);
                }

                $jsonAttachments = $this->formatKillData($data, $watchedEntities);

                $this->pushToSlack($jsonAttachments, $slackHook);
            }
        }

        return true;
    }

    /**
     * Compare each entities from kill to each watched entities
     *
     * @param $data
     * @param $watchedEntities
     * @return bool
     */
    private function isWatchedKill($data, $watchedEntities)
    {

        $killmail = $data->{'package'}->{'killmail'};

        $isVictimWatched = $this->isVictimWatched($killmail, $watchedEntities);
        $isAttackerWatched = $this->isAttackerWatched($killmail, $watchedEntities);
        $isSystemWatched = $this->isSystemWatched($killmail, $watchedEntities);

        return $isVictimWatched || $isAttackerWatched || $isSystemWatched;
    }

    /**
     * Compares victim to watched entities
     *
     * @param $killmail
     * @param $watchedEntities
     * @return bool
     */
    private function isVictimWatched($killmail, $watchedEntities)
    {

        $victim = $killmail->{'victim'};

        if (isset($victim->{'corporation_id'})) {
            if (in_array($victim->{'corporation_id'}, $watchedEntities['corporations'])) {
                return true;
            }
        }

        if (isset($victim->{'alliance_id'})) {
            if (in_array($victim->{'alliance_id'}, $watchedEntities['alliances'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compares attackers to watched entities
     *
     * @param $killmail
     * @param $watchedEntities
     * @return bool
     */
    private function isAttackerWatched($killmail, $watchedEntities)
    {

        $attackers = $killmail->{'attackers'};

        foreach ($attackers as $attacker) {

            if (isset($attacker->{'corporation_id'})) {
                if (in_array($attacker->{'corporation_id'}, $watchedEntities['corporations'])) {
                    return true;
                }
            }

            if (isset($attacker->{'alliance_id'})) {
                if (in_array($attacker->{'alliance_id'}, $watchedEntities['alliances'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Compare watched systems to kill's system
     *
     * @param $killmail
     * @param $watchedEntities
     *
     * @return bool
     */
    private function isSystemWatched($killmail, $watchedEntities)
    {
        if (in_array($killmail->{'solar_system_id'}, $watchedEntities['systems'])) {
            return true;
        }

        return false;
    }

    /**
     * Retrieves ship name from ESI
     *
     * @param $victimShipId
     *
     * @return string
     */
    private function getShipName($victimShipId)
    {

        if (in_array($victimShipId, $this->knownShips)) {
            return $this->knownShips[$victimShipId];
        }

        $json = $this->curlRequest(Settings::$ESI_URL . "v3/universe/types/$victimShipId/");
        $data = json_decode($json);

        $shipName = $data->{'name'};
        $this->knownShips[$victimShipId] = $shipName;

        return $shipName;
    }

    /**
     * Retrieves character name from ESI
     *
     * @param $characterId
     *
     * @return string
     */
    private function getCharacterName($characterId)
    {

        if (in_array($characterId, $this->knownCharacters)) {
            return $this->knownCharacters[$characterId];
        }

        $json = $this->curlRequest(Settings::$ESI_URL . "v4/characters/$characterId/");
        $data = json_decode($json);

        $characterName = $data->{'name'};
        $this->knownCharacters[$characterId] = $characterName;

        return $characterName;
    }

    /**
     * Retrieves corporation name from ESI
     *
     * @param $corporationId
     *
     * @return string
     */
    private function getCorporationName($corporationId)
    {

        if (in_array($corporationId, $this->knownCorporations)) {
            return $this->knownCorporations[$corporationId];
        }

        $json = $this->curlRequest(Settings::$ESI_URL . "v4/corporations/$corporationId/");
        $data = json_decode($json);

        $corporationName = $data->{'name'};
        $this->knownCorporations[$corporationId] = $corporationName;

        return $corporationName;
    }

    /**
     * @param $url
     *
     * @return bool|string
     */
    private function curlRequest($url)
    {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, Settings::$HTTP_HEADER);

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    /**
     * Push the formatted kill to slack
     *
     * @param $killData
     * @param $slackHook
     */
    private function pushToSlack($killData, $slackHook)
    {

        if (Settings::$DEBUG) {
            print_r($killData);
        }

        $data = "payload=" . json_encode($killData);

        $ch = curl_init($slackHook);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Stores the kill in a file as json
     *
     * @param $killId
     * @param $data
     */
    private function storeKillJson($killId, $data)
    {

        $directoryName = __DIR__ . DIRECTORY_SEPARATOR . Settings::$KILL_LOG_FOLDER;

        if (!file_exists($directoryName)) {
            mkdir($directoryName, 0755, true);
        }

        $fileName = $directoryName . DIRECTORY_SEPARATOR . $killId . '.json';

        $file = fopen($fileName, 'a+');
        fwrite($file, $data);
        fclose($file);
    }

    /**
     * Retrieves missing information and format kill data for slack
     *
     * @param $data
     * @param $watchedEntities
     *
     * @return stdClass
     */
    protected function formatKillData($data, $watchedEntities): stdClass
    {

        $killmail = $data->{'package'}->{'killmail'};
        $zkb = $data->{'package'}->{'zkb'};
        $jsonKill = new stdClass();
        $jsonAttachments = new stdClass();

        $killer = null;
        $noVictim = false;
        $attackerCount = count($killmail->{'attackers'});
        $killId = $killmail->{'killmail_id'};

        // Looking up final blow
        foreach ($killmail->{'attackers'} as $attacker) {
            if ($attacker->{'final_blow'} == true) {
                $killer = $attacker;
            }
        }

        // Handling NPC kills
        if (!isset($killer->{'character_id'})) {
            $killerName = $this->getShipName($killer->{'ship_type_id'});
        } else {
            $killerName = $this->getCharacterName($killer->{'character_id'});
        }

        // Handling case with no pilots victims
        $victim = $killmail->{'victim'};
        if (!isset($victim->{'character_id'})) {
            $noVictim = true;
            $victimName = $this->getCorporationName($victim->{'corporation_id'});
        } else {
            $victimName = $this->getCharacterName($victim->{'character_id'});
        }

        $victimShipId = $victim->{'ship_type_id'};
        $victimShipName = $this->getShipName($victimShipId);

        $isVictimCorpWatched = in_array($victim->{'corporation_id'}, $watchedEntities['corporations']);
        $isVictimAllianceWatched =
            isset($victim->{'alliance'}) &&
            in_array($victim->{'alliance_id'}, $watchedEntities['alliances']);

        // Dissociating kill and loss
        if ($isVictimAllianceWatched || $isVictimCorpWatched) {

            $jsonKill->fallback = "$victimName's $victimShipName got killed by $killerName";
            if (isset($killer->{'corporation_id'})) {
                $jsonKill->fallback .= ' (' . $this->getCorporationName($killer->{'corporation_id'}) . ')';
            }
            $jsonKill->color = 'danger';

        } else {
            $victimCorpName = $this->getCorporationName($victim->{'corporation_id'});

            if ($noVictim) {
                $jsonKill->fallback = "$killerName killed $victimName's $victimShipName";
            } else {
                $jsonKill->fallback = "$killerName killed $victimName's $victimShipName ($victimCorpName)";
            }

            $jsonKill->color = 'good';
        }


        /*
         * Formatting data for slack
         */

        $jsonKill->title = $jsonKill->fallback;
        $jsonKill->title_link = "https://zkillboard.com/kill/$killId/";
        $jsonKill->thumb_url = "https://imageserver.eveonline.com/Render/" . $victimShipId . "_64.png";

        $killValue = number_format($zkb->{'totalValue'}, 2, ',', ' ');

        $jsonKillValue = new stdClass();
        $jsonKillValue->title = 'Value';
        $jsonKillValue->value = $killValue . ' ISK';
        $jsonKillValue->short = 'true';

        $jsonTotalAttackers = new stdClass();
        $jsonTotalAttackers->title = 'Pilots involved';
        $jsonTotalAttackers->value = $attackerCount;
        $jsonTotalAttackers->short = 'true';

        $jsonKill->fields = [$jsonKillValue, $jsonTotalAttackers];
        $jsonAttachments->attachments = [$jsonKill];

        return $jsonAttachments;
    }

}
