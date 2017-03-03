<?php

namespace Tagcade\Service\Integration;


use Pheanstalk\PheanstalkInterface;
use Tagcade\Service\Core\TagcadeRestClientInterface;

class IntegrationActivator implements IntegrationActivatorInterface
{
    /** @var TagcadeRestClientInterface */
    protected $restClient;

    /** @var PheanstalkInterface */
    protected $pheanstalk;

    /** @var string */
    protected $fetcherWorkerTube;

    /** @var int */
    protected $pheanstalkTTR;

    public function __construct(TagcadeRestClientInterface $restClient, PheanstalkInterface $pheanstalk, $fetcherWorkerTube, $pheanstalkTTR)
    {
        $this->restClient = $restClient;
        $this->pheanstalk = $pheanstalk;
        $this->fetcherWorkerTube = $fetcherWorkerTube;
        $this->pheanstalkTTR = $pheanstalkTTR;
    }

    /**
     * @inheritdoc
     */
    public function createExecutionJobs()
    {
        /* get all dataSource-integrations that to be executed, from ur api */
        /*
         * for test:
         * $dataSourceIntegrations = [
         *  [
         *      'dataSource' => [
         *          'id' => 1
         *      ],
         *      'integration' => [
         *          'id' => 2,
         *          'canonicalName' => 'rubicon',
         *          'params' => [
         *               'username',
         *               'password'
         *          ]
         *      ],
         *      'params' => [
         *          'username' => 'admin',
         *          'password' => '1A2B3C4D5E6F'
         *      ]
         *  ]
         * ];
         */
        $dataSourceIntegrations = $this->restClient->getDataSourceIntegrationToBeExecuted();
        if (!is_array($dataSourceIntegrations)) {
            return false;
        }

        foreach ($dataSourceIntegrations as $dataSourceIntegration) {
            /* create new job for execution */
            $createJobResult = $this->createExecutionJob($dataSourceIntegration);

            if (!$createJobResult) {
                continue;
            }

            /* update last execution time */
            $this->updateLastExecutionTime($dataSourceIntegration);
        }

        return true;
    }

    /**
     * create execution job for dataSourceIntegration
     *
     * @param $dataSourceIntegration
     * @return bool
     */
    private function createExecutionJob($dataSourceIntegration)
    {
        // TODO: validate key in array before processing...
        $publisherId = $dataSourceIntegration['dataSource']['publisher']['id'];
        $integrationCName = $dataSourceIntegration['integration']['canonicalName'];
        $dataSourceId = $dataSourceIntegration['dataSource']['id'];
        $params = $dataSourceIntegration['params']; // params with key=>value pair
        $paramKeys = $dataSourceIntegration['integration']['params']; // param keys only

        /* transform params from {key, value} to {<key> => <value>} */
        $transformedParams = [];
        foreach ($params as $param) {
            $transformedParams[$param['key']] = $param['value'];
        }

        /* create job data */
        $job = new \stdClass();
        $job->publisherId = $publisherId;
        $job->integrationCName = $integrationCName;
        $job->dataSourceId = $dataSourceId;
        $job->params = json_encode($transformedParams);
        $job->paramKeys = $paramKeys; // TODO: for validate params only

        /* create job payload. 'task' and 'params' keys are due to worker code base */
        $payload = new \stdClass();
        $payload->task = 'getPartnerReport';
        $payload->params = $job;

        /** @var PheanstalkInterface $pheanstalk */
        $this->pheanstalk
            ->useTube($this->fetcherWorkerTube)
            ->put(
                json_encode($payload),
                PheanstalkInterface::DEFAULT_PRIORITY,
                PheanstalkInterface::DEFAULT_DELAY,
                $this->pheanstalkTTR
            );

        return true;
    }

    /**
     * update Last Execution Time
     *
     * @param $dataSourceIntegration
     * @return mixed
     */
    private function updateLastExecutionTime($dataSourceIntegration)
    {
        $dataSourceIntegrationId = $dataSourceIntegration['id'];
        $currentTime = new \DateTime();

        return $this->restClient->updateLastExecutionTimeForIntegrationByCName($dataSourceIntegrationId, $currentTime);
    }
}