<?php

namespace Tngnt\PBI\API;

use Tngnt\PBI\Client;
use Tngnt\PBI\Model\Dataset as DatasetModel;
use Tngnt\PBI\Response;

/**
 * Class Dataset.
 */
class Dataset
{
    const DATASET_URL = 'https://api.powerbi.com/v1.0/myorg/datasets';
    const GROUP_DATASET_URL = 'https://api.powerbi.com/v1.0/myorg/groups/%s/datasets';
    const REFRESH_DATASET_URL = 'https://api.powerbi.com/v1.0/myorg/datasets/%s/refreshes';
    const GROUP_REFRESH_DATASET_URL = 'https://api.powerbi.com/v1.0/myorg/groups/%s/datasets/%s/refreshes';

    /**
     * The SDK client
     *
     * @var Client
     */
    private $client;

    /**
     * Dataset constructor.
     *
     * @param Client $client The SDK client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Retrieves the datasets from the PowerBI API
     *
     * @param string|null $groupId An optional group ID
     *
     * @return Response
     */
    public function get($groupId = null)
    {
        $url = $this->getUrl($groupId);

        $response = $this->client->request(Client::METHOD_GET, $url);

        return $this->client->generateResponse($response);
    }

    public function getByName($groupId, $name)
    {
        $url = $this->getUrl($groupId);

        $response = $this->client->request(Client::METHOD_GET, $url);

        $datasets =  $this->client->generateResponse($response)->toArray();
        foreach ($datasets['value'] as $dataset){

            if ($dataset['name'] == $name){
                return $dataset;
            }
        }
    }

    public function delete($groupId = null, $datasetId = null){

        $url = $this->getUrl($groupId).'/'.$datasetId;

        $response = $this->client->request(Client::METHOD_DELETE, $url);

        return $this->client->generateResponse($response);
    }

    /**
     * Refresh the dataset from the PowerBI API
     *
     * @param string      $datasetId An dataset ID
     * @param string|null $groupId   An optional group ID
     * @param bool|null   $notify    set if user recibe notify mail
     *
     * @return Response
     */
    public function refresh($groupId = null, $datasetId = null, $notify = true)
    {
        $url = $this->getRefreshUrl($groupId, $datasetId);
        if ($notify) {
            $response = $this->client->request(Client::METHOD_POST, $url, ['notifyOption' => 'MailOnFailure']);
        } else {
            $response = $this->client->request(Client::METHOD_POST, $url);
        }



        return $this->client->generateResponse($response);
    }
    

    public function cancelRefresh($groupId, $datasetId, $refreshId)
    {
        $url = "https://api.powerbi.com/v1.0/myorg/groups/$groupId/datasets/$datasetId/refreshes/$refreshId";
        $response = $this->client->request(Client::METHOD_DELETE, $url);
        $response =  $this->client->generateResponse($response)->toArray();

        return $response;
    }

    public function getRefreshHistory($workspaceId, $datasetId){
        if (!$workspaceId || !$datasetId){
            return false;
        }
        $response = $this->client->request('GET', "https://api.powerbi.com/v1.0/myorg/groups/$workspaceId/datasets/$datasetId/refreshes");
        $response = $this->client->generateResponse($response);
        $response = $response->toArray();

        $refreshes = $response['value'];
        foreach ($refreshes as $key => $refresh){
            $endTime = time();
            if (array_key_exists('endTime', $refresh)){
                $endTime = strtotime($refresh['endTime']);
            }

            $durationMinutes = round(($endTime - strtotime($refresh['startTime'])) / 60,2);
            $refreshes[$key]['duration'] = $durationMinutes;

        }
        return $refreshes;
    }

    public function getParameters($workspaceId, $datasetId){
        if (!$workspaceId || !$datasetId){
            return false;
        }
        $response = $this->client->request('GET', "https://api.powerbi.com/v1.0/myorg/groups/$workspaceId/datasets/$datasetId/parameters");
        $response = $this->client->generateResponse($response);
        $response = $response->toArray();

        $response = $response['value'];
        $return = array();
        foreach ($response as $key => $value){
            $return[$value['name']] = $value['currentValue'];
        }

        return $return;
    }

    public function getLastRefresh($workspaceId, $datasetId)
    {
        $refreshes = $this->getRefreshHistory($workspaceId, $datasetId);
        return $refreshes[0];
    }

    /**
     * Create a new dataset on PowerBI.
     *
     * @param DatasetModel $dataset The dataset model
     * @param string|null  $groupId An optional group ID
     *
     * @return Response
     */
    public function createDataset(DatasetModel $dataset, $groupId = null)
    {
        $url = $this->getUrl($groupId);

        $response = $this->client->request(client::METHOD_POST, $url, $dataset);

        return $this->client->generateResponse($response);
    }

    /**
     * Helper function to format the request URL.
     *
     * @param string|null $groupId An optional group ID
     *
     * @return string
     */
    private function getUrl($groupId)
    {
        if ($groupId) {
            return sprintf(self::GROUP_DATASET_URL, $groupId);
        }

        return self::DATASET_URL;
    }

    /**
     * Helper function to format the request URL.
     *
     * @param string      $datasetId id from dataset
     * @param string|null $groupId   An optional group ID
     *
     * @return string
     */
    private function getRefreshUrl($groupId, $datasetId)
    {
        if ($groupId) {
            return sprintf(self::GROUP_REFRESH_DATASET_URL, $groupId, $datasetId);
        }

        return sprintf(self::REFRESH_DATASET_URL, $datasetId);
    }
}
