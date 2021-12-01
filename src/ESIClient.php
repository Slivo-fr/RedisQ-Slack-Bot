<?php

namespace Killbot;

use Exception;
use Settings;

/**
 * Provide an object to interface with ESI API
 * Class ESIClient
 * @package Killbot
 */
class ESIClient
{
    protected $knownEntities = [
        'corporations' => [],
        'ships' => [],
        'characters' => [],
    ];

    /**
     * Retrieves ship name from ESI
     *
     * @param $shipId
     *
     * @return string
     * @throws Exception
     */
    public function getShipName($shipId)
    {

        return $this->getEntityName(
            $shipId,
            'ships',
            Settings::$ESI_URL."v3/universe/types/$shipId/"
        );
    }

    /**
     * Retrieves character name from ESI
     *
     * @param $characterId
     *
     * @return string
     * @throws Exception
     */
    public function getCharacterName($characterId)
    {

        return $this->getEntityName(
            $characterId,
            'characters',
            Settings::$ESI_URL."v5/characters/$characterId/"
        );
    }

    /**
     * Retrieves corporation name from ESI
     *
     * @param $corporationId
     *
     * @return string
     * @throws Exception
     */
    public function getCorporationName($corporationId)
    {

        return $this->getEntityName(
            $corporationId,
            'corporations',
            Settings::$ESI_URL."v4/corporations/$corporationId/"
        );
    }

    /**
     * Requests the name property of a given entity, using the provided endpoint
     * @param $id
     * @param $type
     * @param $endpoint
     * @return mixed|string
     * @throws Exception
     */
    protected function getEntityName($id, $type, $endpoint)
    {

        if (in_array($id, $this->knownEntities[$type])) {
            return $this->knownEntities[$type][$id];
        }

        $name = $this->requestEntityProperty(
            $endpoint,
            'name'
        );

        $this->knownEntities[$type][$id] = $name;

        return $name;
    }

    /**
     * Retrieves an entity property from the given endpoint response
     * @param $endpoint
     * @param $property
     * @return string
     * @throws Exception
     */
    protected function requestEntityProperty($endpoint, $property)
    {
        $json = CurlWrapper::curlRequest($endpoint);
        $data = json_decode($json, true);

        if (isset($data['error'])) {
            $error = 'ESI error : '.$data['error'].' on '.$endpoint;
            throw new Exception($error);
        } else {
            if (!isset($data[$property])) {
                $error = 'ESI error : non-existent property '.$property.' on '.$endpoint;
                throw new Exception($error);
            } else {
                $value = $data[$property];
            }
        }

        return $value;
    }

}