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
            $createJobResult = $this->createExecutionJob($dataSourceIntegrationSchedule['dataSourceIntegration'], $dataSourceIntegrationSchedule['id']);

            if (!$createJobResult) {
                continue;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function createExecutionJobForDataSource($dataSourceId, $customParams = null, $isForceRun = false, $isUpdateNextExecute = false)
    {
        $dataSourceIntegration = null;

        /* get all dataSource-integration-schedules from ur api */
        /* see sample json of dataSourceIntegrationSchedules from comment in createExecutionJobs */
        $dataSourceIntegrationSchedules = $isForceRun
            /* get all dataSource-integration-schedules without schedule config */
            ? $this->restClient->getDataSourceIntegrationSchedulesByDataSource($dataSourceId)
            /* get all dataSource-integration-schedules with schedule config */
            : $this->restClient->getDataSourceIntegrationSchedulesToBeExecuted($dataSourceId);

        if (!is_array($dataSourceIntegrationSchedules) || count($dataSourceIntegrationSchedules) < 1) {
            return true;
        }

        $dataSourceIntegrationSchedule = array_values($dataSourceIntegrationSchedules) [0];

        $scheduleId = '';
        if (array_key_exists('dataSourceIntegration', $dataSourceIntegrationSchedule)) {
            $scheduleId = $dataSourceIntegrationSchedule['id'];
            $dataSourceIntegration = $dataSourceIntegrationSchedule['dataSourceIntegration'];
        }

        if (!is_array($dataSourceIntegration)) {
            return true;
        }

        /* Overwrite by custom params if has */
        if (is_array($customParams)) {
            $dataSourceIntegration['originalParams'] = $customParams;
        }

        /* create new job for execution */
        $this->createExecutionJob($dataSourceIntegration, $scheduleId);

        return true;
    }

    /**
     * create execution job for dataSourceIntegration
     *
     * @param $dataSourceIntegration
     * @param $scheduleId
     * @return bool
     */
    private function createExecutionJob($dataSourceIntegration, $scheduleId)
    {
        // TODO: validate key in array before processing...
        $publisherId = $dataSourceIntegration['dataSource']['publisher']['id'];
        $integrationCName = $dataSourceIntegration['integration']['canonicalName'];
        $dataSourceId = $dataSourceIntegration['dataSource']['id'];
        $params = $dataSourceIntegration['originalParams'];

        $paramKeys = $dataSourceIntegration['integration']['params']; // param keys only
        $backFill = [
            'dataSourceIntegrationId' => $dataSourceIntegration['id'],
            'dataSourceIntegrationScheduleId' => $scheduleId,
            'backFill' => $dataSourceIntegration['backFill'],
            'backFillStartDate' => $dataSourceIntegration['backFillStartDate'],
            'backFillEndDate' => $dataSourceIntegration['backFillEndDate'],
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
        $payload->task = 'executeIntegration';
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
}