<?php

namespace Tagcade\Service\Integration\Integrations\General;

use RestClient\CurlRestClient;
use Tagcade\Service\FileStorageServiceInterface;
use Tagcade\Service\Integration\ConfigInterface;
use Tagcade\Service\Integration\Integrations\IntegrationAbstract;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;

abstract class WebApi extends IntegrationAbstract implements IntegrationInterface
{
    const INTEGRATION_C_NAME = 'general-web-api';

    const URL = null;

    /**
     * @var FileStorageServiceInterface
     */
    private $fileStorage;

    /**
     * GeneralIntegrationAbstract constructor.
     * @param FileStorageServiceInterface $fileStorage
     */
    public function __construct(FileStorageServiceInterface $fileStorage)
    {
        $this->fileStorage = $fileStorage;
    }

    /**
     * @param array $params
     */
    public function buildParams(array $params)
    {
    }

    /**
     * @return null
     */
    public function getHeader()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return 'GET';
    }

    /**
     * @param ConfigInterface $config
     * @return void
     */
    public function run(ConfigInterface $config)
    {
        $url = $this->getReportUrl();

        if ($url === null) {
            // default use general-web-api, so url must be get from config
            $url = $config->getParamValue('url', null);
        }

        $params = $config->getParamValue('params', null);

        $this->buildParams($params);
        $method = $this->getMethod();
        $header = $this->getHeader();

        $reportDatas = $this->doGetData($url, $method, $header, $params);

//        $this->fileStorage->getDownloadPath();
//
//        $this->fileStorage->saveToCSVFile();
    }

    /**
     * @inheritdoc
     */
    public function doGetData($url, $method = 'GET', $header = null, $params = array())
    {
        $curl = new CurlRestClient();
        $responseData = $curl->executeQuery($url, $method, $header, $params);
        $curl->close();

        return $responseData;
    }

    /**
     * @return string
     */
    public function getReportUrl()
    {
        return static::URL;
    }
}