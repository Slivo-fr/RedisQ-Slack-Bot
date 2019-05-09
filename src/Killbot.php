<?php

namespace Killbot;

use Exception;
use Ramsey\Uuid\Uuid;
use Settings;
use stdClass;

class Killbot
{
    /**
     * @var ESIClient
     */
    protected $esiClient;

    /**
     * Runs the bot
     * @throws Exception
     */
    public function run()
    {
        $isFeedEmpty = false;
        $timeout = time() + Settings::$MAX_RUN_TIME;

        // Processing queue
        while (!$isFeedEmpty && time() < $timeout) {

            $isFeedEmpty = !$this->processKill();
        }

        // Processing previously failed kills
        $files = $this->getPendingFiles();

        foreach ($files as $file) {

            Logger::log('Retrying pending kill '.$file, 'INFO');

            $filepath = Utils::getUnprocessedPath().DIRECTORY_SEPARATOR.$file;
            $this->processKill(
                json_decode(file_get_contents($filepath))
            );

            unlink($filepath);
        }
    }

    /**
     * Fetch and process one kill from RedisQ
     * Return false if the queue is empty, true otherwise
     *
     * If provided, uses $data as datasource
     *
     * @param string $data
     * @return bool
     * @throws Exception
     */
    private function processKill($data = null)
    {

        if ($data === null) {
            $this->esiClient = new ESIClient();
            $rawOutput = CurlWrapper::curlRequest(Settings::$REDISQ_URL);

            $data = json_decode($rawOutput);
        }

        if (!isset($data->{'package'}) || $data->{'package'} == null) {
            return false;
        }

        try {

            foreach (Settings::$watchingConfigurations as $watchingConfiguration) {

                $slackHook = $watchingConfiguration['SLACK_HOOK'];
                $watchedEntities = $watchingConfiguration['WATCHED_ENTITIES'];

                // Only process kill that match settings entities
                if ($this->isWatchedKill($data, $watchedEntities)) {

                    if (Settings::$DEBUG) {
                        Logger::storeKillJson($data->{'package'}->{'killID'}, json_encode($data));
                    }

                    $jsonAttachments = $this->formatKillData($data, $watchedEntities);

                    $this->pushToSlack($jsonAttachments, $slackHook);
                }
            }

            return true;
        } catch (Exception $exception) {

            if (!isset($rawOutput)) {
                $rawOutput = json_encode($data);
            }

            if ($rawOutput != '') {

                Utils::writeFile(
                    $rawOutput,
                    Utils::getUnprocessedPath(),
                    Uuid::uuid4()->toString() . '.kill',
                    'w'
                );
            }

            throw $exception;
        }
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
     * Push the formatted kill to slack
     *
     * @param $killData
     * @param $slackHook
     * @throws Exception
     */
    private function pushToSlack($killData, $slackHook)
    {

        if (Settings::$DEBUG) {
            print_r($killData);
        }

        $data = "payload=" . json_encode($killData);

        CurlWrapper::post($slackHook, $data);
    }

    /**
     * Retrieves missing information and format kill data for slack
     *
     * @param $data
     * @param $watchedEntities
     *
     * @return stdClass
     * @throws Exception
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

        $killerName = 'Unknown entity';

        // Handling NPC kills
        if ($killer) {
            if (isset($killer->{'character_id'})) {
                $killerName = $this->esiClient->getCharacterName($killer->{'character_id'});
            } else {
                if (isset($killer->{'ship_type_id'})) {
                    $killerName = $this->esiClient->getShipName($killer->{'ship_type_id'});
                }
            }
        }

        // Handling case with no pilots victims
        $victim = $killmail->{'victim'};
        if (!isset($victim->{'character_id'})) {
            $noVictim = true;
            $victimName = $this->esiClient->getCorporationName($victim->{'corporation_id'});
        } else {
            $victimName = $this->esiClient->getCharacterName($victim->{'character_id'});
        }

        $victimShipId = $victim->{'ship_type_id'};
        $victimShipName = $this->esiClient->getShipName($victimShipId);

        $isVictimCorpWatched = in_array($victim->{'corporation_id'}, $watchedEntities['corporations']);
        $isVictimAllianceWatched =
            isset($victim->{'alliance'}) &&
            in_array($victim->{'alliance_id'}, $watchedEntities['alliances']);

        // Dissociating kill and loss
        if ($isVictimAllianceWatched || $isVictimCorpWatched) {

            $jsonKill->fallback = "$victimName's $victimShipName got killed by $killerName";
            if (isset($killer->{'corporation_id'})) {
                $jsonKill->fallback .= ' ('.$this->esiClient->getCorporationName($killer->{'corporation_id'}).')';
            }
            $jsonKill->color = 'danger';

        } else {
            $victimCorpName = $this->esiClient->getCorporationName($victim->{'corporation_id'});

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
        $jsonKill->thumb_url = "https://imageserver.eveonline.com/Type/" . $victimShipId . "_64.png";

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

    /**
     * @return array
     */
    protected function getPendingFiles(): array
    {
        $unprocessedPath = Utils::getUnprocessedPath();
        Utils::createPath($unprocessedPath);
        $files = scandir($unprocessedPath);

        if ($files) {
            $files = array_diff($files, array('..', '.'));
        } else {
            $files = [];
        }

        return $files;
    }
}
