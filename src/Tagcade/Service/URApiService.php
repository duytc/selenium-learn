<?php

namespace Tagcade\Service;

use Monolog\Logger;
use RestClient\CurlRestClient;

class URApiService implements URApiServiceInterface
{
    /**
     * @var string
     */
    private $uploadJsonDataLink;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * URApiService constructor.
     * @param $uploadJsonDataLink
     * @param Logger $logger
     */
    public function __construct($uploadJsonDataLink, Logger $logger)
    {
        $this->uploadJsonDataLink = $uploadJsonDataLink;
        $this->logger = $logger;
    }

    public function addJsonDataToDataSource(int $dataSourceId, array $rows, $header = null)
    {
        $uploadUrl = str_replace('{id}', $dataSourceId, $this->uploadJsonDataLink);

        $curl = new CurlRestClient();
        $responseData = $curl->executeQuery($uploadUrl, 'POST', $header, $rows);
        $curl->close();

        $responseData = json_decode($responseData, true);

        $this->logger->alert($responseData['message']);

        return $responseData;
    }
}