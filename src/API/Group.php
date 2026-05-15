<?php

namespace Tngnt\PBI\API;

use Tngnt\PBI\Client;

/**
 * Class Group
 *
 * @package Tngnt\PBI\API
 */
class Group
{
    const GROUP_URL = "https://api.powerbi.com/v1.0/myorg/groups";

    /**
     * The SDK client
     *
     * @var Client
     */
    private $client;

    /**
     * Table constructor.
     *
     * @param Client $client The SDK client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Returns all the groups the user belongs to on PowerBI
     *
     * @return \Tngnt\PBI\Response
     */
    public function getGroups()
    {
        $response = $this->client->request(Client::METHOD_GET, self::GROUP_URL);

        return $this->client->generateResponse($response);
    }

    public function getGroupByName(string $name): ?array
    {
        $response = $this->getGroups()->toArray();
        $groups = $response['value'] ?? [];
    
        foreach ($groups as $group) {
            if (isset($group['name']) && $group['name'] === $name) {
                return $group;
            }
        }
    
        return null;
    }

    public function createGroup(string $name): array
    {
        $response = $this->client->request(Client::METHOD_POST, self::GROUP_URL, ['name' => $name]);
        return $this->client->generateResponse($response)->toArray();
    }
    
    public function getOrCreateGroup(string $name): array
    {
        $group = $this->getGroupByName($name);
        return $group ?: $this->createGroup($name);
    }
}
