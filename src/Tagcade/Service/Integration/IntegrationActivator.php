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
            $createJobResult = $this->createExecutionJob($dataSourceIntegrationSchedule['dataSourceIntegration']);

            if (!$createJobResult) {
                continue;
            }

            /* update next execution at */
            $this->updateNextExecuteAt($dataSourceIntegrationSchedule);

            /* update backFill Executed if need */
            $dataSourceIntegration = $dataSourceIntegrationSchedule['dataSourceIntegration'];
            $this->updateBackFillExecuted($dataSourceIntegration);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function createExecutionJobForDataSourceWithSchedule($dataSourceId, $customParams = null, $isScheduleUpdated = false)
    {
        $dataSourceIntegrationSchedulesRaw = $this->restClient->getDataSourceIntegrationSchedulesToBeExecuted();

        $dataSourceIntegrationSchedules = array_filter($dataSourceIntegrationSchedulesRaw, function ($dataSourceIntegrationSchedule) use ($dataSourceId) {
            return $dataSourceId = $dataSourceIntegrationSchedule['dataSourceIntegration']['dataSource']['id'] == $dataSourceId;
        });

        if (!is_array($dataSourceIntegrationSchedules)) {
            return false;
        }

        foreach ($dataSourceIntegrationSchedules as $dataSourceIntegrationSchedule) {
            if (is_array($customParams)) {
                /* Overwrite by custom params */
                $dataSourceIntegrationSchedule['dataSourceIntegration']['originalParams'] = $customParams;
                $dataSourceIntegrationSchedule['dataSourceIntegration']['params'] = $customParams;
            }

            /* create new job for execution */
            $createJobResult = $this->createExecutionJob($dataSourceIntegrationSchedule['dataSourceIntegration']);

            if (!($createJobResult && $isScheduleUpdated)) {
                continue;
            }

            /* update next execution at */
            $this->updateNextExecuteAt($dataSourceIntegrationSchedule);

            /* update backFill Executed if need */
            $dataSourceIntegration = $dataSourceIntegrationSchedule['dataSourceIntegration'];
            $this->updateBackFillExecuted($dataSourceIntegration);
        }


        return true;
    }

    /**
     * @inheritdoc
     */
    public function createExecutionJobForDataSourceWithoutSchedule($dataSourceId, $customParams = null)
    {
        $dataSourceIntegration = null;
        /* get all dataSource-integration-schedule without checking schedule, from ur api */
        /* see sample json of dataSourceIntegrationSchedules from comment in createExecutionJobs */
        $dataSourceIntegrations = $this->restClient->getDataSourceIntegrationSchedulesByDataSource($dataSourceId);
        if (!is_array($dataSourceIntegrations) || count($dataSourceIntegrations) < 1) {
            return true;
        }

        if (is_array($dataSourceIntegrations)) {
            $dataSourceIntegration = array_values($dataSourceIntegrations)[0];
        }

        if ($dataSourceIntegration) {
            if (is_array($customParams)) {
                /* Overwrite by custom params */
                $dataSourceIntegration['originalParams'] = $customParams;
                $dataSourceIntegration['params'] = $customParams;
            }
            /* create new job for execution */
            $createJobResult = $this->createExecutionJob($dataSourceIntegration);
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
        if (array_key_exists('originalParams', $dataSourceIntegration)){
            $params = $dataSourceIntegration['originalParams'];
        } elseif (array_key_exists('params', $dataSourceIntegration)){
            $params = $dataSourceIntegration['params'];
        }

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

    /**
     * update Next Execution At
     *
     * @param $dataSourceIntegrationSchedule
     * @return mixed
     */
    private function updateNextExecuteAt($dataSourceIntegrationSchedule)
    {
        $dataSourceIntegrationScheduleId = $dataSourceIntegrationSchedule['id'];

        return $this->restClient->updateNextExecuteAtForIntegrationSchedule($dataSourceIntegrationScheduleId);
    }

    /**
     * update BackFill Executed
     *
     * @param $dataSourceIntegration
     * @return mixed
     */
    private function updateBackFillExecuted($dataSourceIntegration)
    {
        if (!is_array($dataSourceIntegration)
            || !array_key_exists('id', $dataSourceIntegration)
            || !array_key_exists('backFill', $dataSourceIntegration)
            || !array_key_exists('backFillExecuted', $dataSourceIntegration)
        ) {
            return true;
        }

        // skip if not need backFill
        $isBackFill = (bool)$dataSourceIntegration['backFill'];
        $isBackFillExecuted = (bool)$dataSourceIntegration['backFillExecuted'];
        if (!$isBackFill || $isBackFillExecuted) {
            return true;
        }

        // update backFill executed to true
        $dataSourceIntegrationId = $dataSourceIntegration['id'];
        return $this->restClient->updateBackFillExecutedForIntegration($dataSourceIntegrationId);
    }
}