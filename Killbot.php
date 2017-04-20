<?php


class Killbot {

    protected $knownShipList = array();

    public function run() {

        $isFeedEmpty = false;
        $timeout = time() + Settings::$MAX_RUN_TIME;

        while (!$isFeedEmpty && time() < $timeout) {

            $isFeedEmpty = !$this->processKill();
        }
    }

    /*
     * Fetch and process one kill from RedisQ
     * Return false if the queue is empty, true otherwise
     */
    private function processKill() {

        $rawOutput = $this->curlRequest(Settings::$REDISQ_URL);

        $data = json_decode($rawOutput);

        if (!isset($data->{'package'}) || $data->{'package'} == null) {
            return false;
        }

        $killmail = $data->{'package'}->{'killmail'};

        // Only process kill that match settings entities
        if ($this->isWatchedKill($killmail)) {

            if (Settings::$DEBUG) {
                $this->storeKillJson($data->{'package'}->{'killID'}, $rawOutput);
            }

            $zkb = $data->{'package'}->{'zkb'};
            $jsonKill = new stdClass();
            $jsonAttachments = new stdClass();

            $killer = null;
            $noVictim = false;
            $attackerCount = $killmail->{'attackerCount'};
            $killId = $killmail->{'killID'};

            // Looking up final blow
            foreach ($killmail->{'attackers'} as $attacker) {
                if ($attacker->{'finalBlow'} == true) {
                    $killer = $attacker;
                }
            }

            // Handling NPC kills
            if (!isset($killer->{'character'}->{'name'})) {
                $killerName = $killer->{'shipType'}->{'name'};
            } else {
                $killerName = $killer->{'character'}->{'name'};
            }

            // Handling case with no pilots victims
            $victim = $killmail->{'victim'};
            if (!isset($victim->{'character'}->{'name'})) {
                $noVictim = true;
                $victimName = $victim->{'corporation'}->{'name'};
            } else {
                $victimName = $victim->{'character'}->{'name'};
            }

            $victimShipId = $victim->{'shipType'}->{'id'};
            $victimShipName = $this->getShipName($victimShipId);

            $isVictimCorpWatched = in_array($victim->{'corporation'}->{'id'}, Settings::$WATCHED_ENTITIES['corporations']);
            $isVictimAllianceWatched =
                isset($victim->{'alliance'}) &&
                in_array($victim->{'alliance'}->{'id'}, Settings::$WATCHED_ENTITIES['alliances']);

            // Dissociating kill and loss
            if ($isVictimAllianceWatched || $isVictimCorpWatched) {

                $jsonKill->fallback = "$victimName's $victimShipName got killed by $killerName";
                if (isset($killer->{'corporation'}->{'name'})) {
                    $jsonKill->fallback .= ' (' . $killer->{'corporation'}->{'name'} . ')';
                }
                $jsonKill->color = 'danger';

            } else {
                
                $victimCorpName = $victim->{'corporation'}->{'name'};

                if($noVictim) {
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
            $jsonKill->thumb_url = "https://imageserver.eveonline.com/Render/".$victimShipId."_64.png";

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

            $this->pushToSlack($jsonAttachments);
        }


        return true;
    }

    private function isWatchedKill($killmail) {

        $isVictimWatched = $this->isVictimWatched($killmail);
        $isAttackerWatched = $this->isAttackerWatched($killmail);
        $isSystemWatched = $this->isSystemWatched($killmail);

        return $isVictimWatched || $isAttackerWatched || $isSystemWatched;
    }

    private function isVictimWatched($killmail) {

        $victim = $killmail->{'victim'};

        if (isset($victim->{'corporation'}->{'id'})) {
            if (in_array($victim->{'corporation'}->{'id'}, Settings::$WATCHED_ENTITIES['corporations'])){
                return true;
            }
        }

        if (isset($victim->{'alliance'}->{'id'})) {
            if (in_array($victim->{'alliance'}->{'id'}, Settings::$WATCHED_ENTITIES['alliances'])){
                return true;
            }
        }

        return false;
    }

    private function isAttackerWatched($killmail) {

        $attackers = $killmail->{'attackers'};

        foreach ($attackers as $attacker) {

            if (isset($attacker->{'corporation'}->{'id'})) {
                if (in_array($attacker->{'corporation'}->{'id'}, Settings::$WATCHED_ENTITIES['corporations'])){
                    return true;
                }
            }

            if (isset($attacker->{'alliance'}->{'id'})) {
                if (in_array($attacker->{'alliance'}->{'id'}, Settings::$WATCHED_ENTITIES['alliances'])){
                    return true;
                }
            }
        }

        return false;
    }

    private function isSystemWatched($killmail)
    {
        if (in_array($killmail->{'solarSystem'}->{'id'}, Settings::$WATCHED_ENTITIES['systems'])) {
            return true;
        }

        return false;
    }

    private function getShipName($victimShipId) {

        if (in_array($victimShipId, $this->knownShipList)) {
            return $this->knownShipList[$victimShipId];
        }

        $json = $this->curlRequest("https://crest-tq.eveonline.com/inventory/types/$victimShipId/");
        $data = json_decode($json);

        $shipName = $data->{'name'};
        $this->knownShipList[$victimShipId] = $shipName;

        return $shipName;
    }

    private function curlRequest ($url) {

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

    private function pushToSlack($killData) {

        $data = "payload=" .json_encode($killData);

        $ch = curl_init(Settings::$SLACK_HOOK);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_exec($ch);
        curl_close($ch);
    }


    private function storeKillJson($killId, $data) {

        $directoryName = __DIR__ . DIRECTORY_SEPARATOR . Settings::$KILL_LOG_FOLDER;

        if (!file_exists($directoryName)) {
            mkdir($directoryName, 0755, true);
        }

        $fileName = $directoryName . DIRECTORY_SEPARATOR . $killId . '.json';

        $file = fopen($fileName, 'a+');
        fwrite($file, $data);
        fclose($file);
    }

}

