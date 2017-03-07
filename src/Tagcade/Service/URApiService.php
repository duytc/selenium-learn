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

    public function addJsonDataToDataSource($dataSourceId, array $rows, $header = null)
    {
        $uploadUrl = $this->uploadJsonDataLink;

        $curl = new CurlRestClient($uploadUrl);
        $responseData = $curl->post("", array(
            'source' => 'integration',
            'ids' => json_encode($dataSourceId),
            'data' => $rows
        ));
        $curl->close();

        $responseData = json_decode($responseData, true);

        $this->logger->alert($responseData['message']);

        return $responseData;
    }
}