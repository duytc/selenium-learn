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
        /* get all dataSource-integration-schedule that to be executed, from ur api */
        /*
         * for test:
         * $dataSourceIntegrationSchedules = [
         *    [
         *       'id' => 1,
         *       'scheduleType' => 'checkEvery|checkAt',
         *       'executeAt' => 1,
         *       dataSourceIntegration = [
         *           'dataSource' => [
         *               'id' => 1
         *           ],
         *           'integration' => [
         *               'id' => 2,
         *               'canonicalName' => 'rubicon',
         *               'params' => [
         *                    [ 'key' => 'username', 'type' => 'plainText' ],
         *                    [ 'key' => 'password', 'type' => 'secure' ],
         *                    ...
         *               ]
         *           ],
         *           'originalParams' => [
         *               [ 'key' => 'username', 'type' => 'plainText', 'value' => 'admin' ],
         *               [ 'key' => 'password', 'type' => 'secure', 'value' => '1A2B3C4D5E6F' ],
         *               ...
         *           ]
         *       ]
         *    ],
         *    ...
         * ];
         */
        $dataSourceIntegrationSchedules = $this->restClient->getDataSourceIntegrationSchedulesToBeExecuted();
        if (!is_array($dataSourceIntegrationSchedules)) {
            return false;
        }

        foreach ($dataSourceIntegrationSchedules as $dataSourceIntegrationSchedule) {
            /* create new job for execution */
            $createJobResult = $this->createExecutionJob($dataSourceIntegrationSchedule);

            if (!$createJobResult) {
                continue;
            }

            /* update next execution at */
            $this->updateNextExecuteAt($dataSourceIntegrationSchedule);
        }

        return true;
    }

    /**
     * create execution job for dataSourceIntegration
     *
     * @param $dataSourceIntegrationSchedule
     * @return bool
     */
    private function createExecutionJob($dataSourceIntegrationSchedule)
    {
        // TODO: validate key in array before processing...
        $dataSourceIntegration = $dataSourceIntegrationSchedule['dataSourceIntegration'];
        $publisherId = $dataSourceIntegration['dataSource']['publisher']['id'];
        $integrationCName = $dataSourceIntegration['integration']['canonicalName'];
        $dataSourceId = $dataSourceIntegration['dataSource']['id'];
        $params = $dataSourceIntegration['originalParams']; // original params as array of { key, value, type }
        $paramKeys = $dataSourceIntegration['integration']['params']; // param keys only
        $backFill = [
            'backFill' => $dataSourceIntegration['backFill'],
            'backFillStartDate' => $dataSourceIntegration['backFillStartDate'],
            'backFillForce' => $dataSourceIntegration['backFillForce'],
            'backFillExecuted' => $dataSourceIntegration['backFillExecuted']
        ];

        /* create job data */
        $job = new \stdClass();
        $job->publisherId = $publisherId;
        $job->integrationCName = $integrationCName;
        $job->dataSourceId = $dataSourceId;
        $job->params = json_encode($params);
        $job->paramKeys = $paramKeys; // TODO: for validate params only
        $job->backFill = json_encode($backFill);

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
     * @param $dataSourceIntegrationSchedule
     * @return mixed
     */
    private function updateNextExecuteAt($dataSourceIntegrationSchedule)
    {
        $dataSourceIntegrationScheduleId = $dataSourceIntegrationSchedule['id'];

        return $this->restClient->updateNextExecuteAtForIntegrationSchedule($dataSourceIntegrationScheduleId);
    }
}