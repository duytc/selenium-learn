<?php

namespace Tagcade\Service\Integration\Integrations\Pubvantage;

use Exception;
use Psr\Log\LoggerInterface;
use Tagcade\Service\FileStorageService;
use Tagcade\Service\Integration\ConfigInterface;
use Tagcade\Service\Integration\Integrations\IntegrationAbstract;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;
use Tagcade\Service\TagcadeApiService;
use Tagcade\Service\URApiService;

class AccountReport extends IntegrationAbstract implements IntegrationInterface
{
    const INTEGRATION_C_NAME = 'pubvantage';
    const TOKEN_URL = 'http://api.tagcade.dev/app_debug.php/api/v1/getToken';
    const REPORT_URL = 'http://api.tagcade.dev/app_dev.php/api/reports/v1/performancereports/accounts/{id}';

    /**
     * @var FileStorageService
     */
    private $fileStorage;
    /**
     * @var TagcadeApiService
     */
    private $tagcadeApi;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var URApiService
     */
    private $URApiService;

    public function __construct(FileStorageService $fileStorage, TagcadeApiService $tagcadeApi, LoggerInterface $logger, URApiService $URApiService)
    {
        $this->fileStorage = $fileStorage;
        $this->tagcadeApi = $tagcadeApi;
        $this->logger = $logger;
        $this->URApiService = $URApiService;
    }

    /**
     * @inheritdoc
     */
    public function run(ConfigInterface $config)
    {
        $allParams = $config->getParams();
        if (!array_key_exists('username', $allParams)) {
            $this->logger->error('Missing username in parameters');
            throw new Exception('Missing username in parameters');
        }
        $username = $allParams['username'];

        if (!array_key_exists('password', $allParams)) {
            $this->logger->error('Missing password in parameters');
            throw new Exception('Missing password in parameters');
        }
        $password = $allParams['password'];

        if (!array_key_exists('startDate', $allParams)) {
            $startDate = (new \DateTime('yesterday'))->format('Y-m-d');
        } else {
            $startDate = $allParams['startDate'];
        }

        if (!array_key_exists('endDate', $allParams)) {
            $endDate = (new \DateTime('yesterday'))->format('Y-m-d');
        } else {
            $endDate = $allParams['endDate'];
        }

        $url = str_replace('{id}', $config->getPublisherId(),self::REPORT_URL);

        if (array_key_exists('group', $allParams)) {
            $group = $allParams['group'];
        } else {
            $group = false;
        }

        $token = $this->tagcadeApi->getToken(self::TOKEN_URL, $username, $password);
        $header = $this->createHeaderData($token);

        $parameterForGetMethod = array('startDate' => $startDate, 'endDate' => $endDate, '$group' => $group);
        $reports = $this->tagcadeApi->getReports($url, 'GET', $header, $parameterForGetMethod);

        $reports= json_decode($reports, true);

        if (empty($reports)) {
            $this->logger->warning('There are not reports');
            return false;
        }

        $fileName = sprintf('%s.csv', bin2hex(random_bytes(10)));
        $path = $this->fileStorage->getDownloadPath($config, $fileName);
        return $this->fileStorage->saveToCSVFile($path, $this->getRows($reports), $this->getColumns($reports));

      //  return $this->URApiService->addJsonDataToDataSource($config->getDataSourceId(), $this->getRows($reports), $header);
    }

    /**
     * @inheritdoc
     */
    protected function createHeaderData($token)
    {
        $header = array('Authorization: Bearer ' . $token);

        return $header;
    }

    /**
     * @param $reports
     * @return array
     */
    protected function getColumns($reports)
    {
        $reports = $this->getRows($reports);

        return array_keys($reports[0]);
    }

    /**
     * @param $reports
     * @return array
     * @throws Exception
     */
    protected function getRows($reports)
    {

        if (!array_key_exists('reports', $reports)) {
            $this->logger->error('There is not "Reports" key in reports');
            throw new Exception('There is not "Reports" key in reports');
        }

        $reportValues = $reports['reports'];
        foreach ($reportValues as $index => $reportValue) {
            foreach ($reportValue as $key => $report) {
                if (is_array($report)) {
                    unset($reportValue[$key]);
                    $reportValues[$index] = $reportValue;
                }
            }
        }

        return array_values($reportValues);
    }
}