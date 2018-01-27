<?php

namespace Tagcade\Service\Integration;

use Pheanstalk\PheanstalkInterface;
use Tagcade\Service\Core\TagcadeRestClient;
use Tagcade\Service\Core\TagcadeRestClientInterface;
use Tagcade\Service\Fetcher\Params\PartnerParams;

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
         *           PartnerParams::PARAM_KEY_DATA_SOURCE => [
         *               'id' => 1
         *           ],
         *           PartnerParams::PARAM_KEY_INTEGRATION => [
         *               'id' => 2,
         *               PartnerParams::PARAM_KEY_CANONICAL_NAME => 'rubicon',
         *               PartnerParams::PARAM_KEY_PARAMS => [
         *                    [ 'key' => 'username', 'type' => 'plainText' ],
         *                    [ 'key' => 'password', 'type' => 'secure' ],
         *                    ...
         *               ]
         *           ],
         *           PartnerParams::PARAM_KEY_ORIGINAL_PARAMS => [
         *               [ 'key' => 'username', 'type' => 'plainText', 'value' => 'admin' ],
         *               [ 'key' => 'password', 'type' => 'secure', 'value' => '1A2B3C4D5E6F' ],
         *               ...
         *           ]
         *       ]
         *    ],
         *    ...
         * ];
         */
        $fetcherSchedules = $this->restClient->getDataSourceIntegrationSchedulesToBeExecuted();
        if (!is_array($fetcherSchedules)) {
            return false;
        }

        $fetcherSchedulesShouldNotRun = [];
        foreach ($fetcherSchedules as $fetcherSchedule) {
            $executeJob = $this->createExecutionJob($fetcherSchedule);

            if (is_string($executeJob)) {
                $fetcherSchedulesShouldNotRun [] = $executeJob;
            }
        }

        if (!empty($fetcherSchedulesShouldNotRun)) {
            return $fetcherSchedulesShouldNotRun;
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
        $fetcherSchedules = $isForceRun
            /* get all dataSource-integration-schedules without schedule config */
            ? $this->restClient->getDataSourceIntegrationSchedulesByDataSource($dataSourceId)
            /* get all dataSource-integration-schedules with schedule config */
            : $this->restClient->getDataSourceIntegrationSchedulesToBeExecuted($dataSourceId);

        if (!is_array($fetcherSchedules) || count($fetcherSchedules) < 1) {
            return true;
        }

        $fetcherSchedulesShouldNotRun = [];
        foreach ($fetcherSchedules as $fetcherSchedule) {
            /* Overwrite by custom params if has */
            if (is_array($customParams)) {
                if (isset($fetcherSchedule[PartnerParams::PARAM_KEY_BACK_FILL_HISTORY])) {
                    $dataSourceIntegration = $fetcherSchedule[PartnerParams::PARAM_KEY_BACK_FILL_HISTORY][PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION];
                    $fetcherSchedule[PartnerParams::PARAM_KEY_BACK_FILL_HISTORY][PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION] = $dataSourceIntegration;
                    $dataSourceIntegration[PartnerParams::PARAM_KEY_ORIGINAL_PARAMS] = $customParams;
                } elseif (isset($fetcherSchedule[PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE])) {
                    $dataSourceIntegration = $fetcherSchedule[PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE][PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION];
                    $dataSourceIntegration[PartnerParams::PARAM_KEY_ORIGINAL_PARAMS] = $customParams;
                    $fetcherSchedule[PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE][PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION] = $dataSourceIntegration;
                }
            }

            /* create new job for execution */
            $executeJob = $this->createExecutionJob($fetcherSchedule, $isForceRun);

            if (is_string($executeJob)) {
                $fetcherSchedulesShouldNotRun [] = $executeJob;
            }
        }

        if (!empty($fetcherSchedulesShouldNotRun)) {
            return $fetcherSchedulesShouldNotRun;
        }

        return true;
    }

    /**
     * create execution job for dataSourceIntegration
     *
     * @param $fetcherSchedule
     * @param bool $isForceRun
     * @return bool
     */
    private function createExecutionJob($fetcherSchedule, $isForceRun = false)
    {
        if (isset($fetcherSchedule[PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE])) {
            $dataSourceIntegration = $fetcherSchedule[PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE][PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION];

            $backFill = [
                PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_ID => $dataSourceIntegration[PartnerParams::PARAM_KEY_ID],
                PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE_ID => $fetcherSchedule[PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE][PartnerParams::PARAM_KEY_ID],
                PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_BACKFILL_HISTORY_ID => null,
                PartnerParams::PARAM_KEY_BACK_FILL => false,
                PartnerParams::PARAM_KEY_BACK_FILL_START_DATE => null,
                PartnerParams::PARAM_KEY_BACK_FILL_END_DATE => null,
                PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE_UUID => $fetcherSchedule[PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE][PartnerParams::PARAM_KEY_UUID],
                PartnerParams::PARAM_KEY_FETCHER_ACTIVATOR_DATASOURCE_FORCE => $isForceRun,
            ];
        } elseif (isset($fetcherSchedule[PartnerParams::PARAM_KEY_BACK_FILL_HISTORY])) {
            $dataSourceIntegration = $fetcherSchedule[PartnerParams::PARAM_KEY_BACK_FILL_HISTORY][PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION];
            $dataSourceIntegration[PartnerParams::PARAM_KEY_BACK_FILL_START_DATE] = $fetcherSchedule[PartnerParams::PARAM_KEY_BACK_FILL_HISTORY][PartnerParams::PARAM_KEY_BACK_FILL_START_DATE];
            $dataSourceIntegration[PartnerParams::PARAM_KEY_BACK_FILL_END_DATE] = $fetcherSchedule[PartnerParams::PARAM_KEY_BACK_FILL_HISTORY][PartnerParams::PARAM_KEY_BACK_FILL_END_DATE];

            $backFill = [
                PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_ID => $dataSourceIntegration[PartnerParams::PARAM_KEY_ID],
                PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE_ID => null,
                PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_BACKFILL_HISTORY_ID => $backFillHistoryId = $fetcherSchedule[PartnerParams::PARAM_KEY_BACK_FILL_HISTORY][PartnerParams::PARAM_KEY_ID],
                PartnerParams::PARAM_KEY_BACK_FILL => true,
                PartnerParams::PARAM_KEY_BACK_FILL_START_DATE => $fetcherSchedule[PartnerParams::PARAM_KEY_BACK_FILL_HISTORY][PartnerParams::PARAM_KEY_BACK_FILL_START_DATE],
                PartnerParams::PARAM_KEY_BACK_FILL_END_DATE => $fetcherSchedule[PartnerParams::PARAM_KEY_BACK_FILL_HISTORY][PartnerParams::PARAM_KEY_BACK_FILL_END_DATE],
                PartnerParams::PARAM_KEY_FETCHER_ACTIVATOR_DATASOURCE_FORCE => $isForceRun,
            ];

        } else {
            return false;
        }

        // TODO: validate key in array before processing...
        $publisherId = $dataSourceIntegration[PartnerParams::PARAM_KEY_DATA_SOURCE][PartnerParams::PARAM_KEY_PUBLISHER][PartnerParams::PARAM_KEY_ID];
        $publisherActive = $dataSourceIntegration[PartnerParams::PARAM_KEY_DATA_SOURCE][PartnerParams::PARAM_KEY_PUBLISHER]['enabled'];
        // check publisher is enabled
        // if publisher is not enabled -> DataSourceIntegration should not run
        if (!$publisherActive) {
            return sprintf('DataSourceIntegration(%d) - dataSource(%d) should not run because publisher(%d) is inactive', $dataSourceIntegration[PartnerParams::PARAM_KEY_ID], $dataSourceIntegration[PartnerParams::PARAM_KEY_DATA_SOURCE][PartnerParams::PARAM_KEY_ID], $publisherId);
        }
        $integrationCName = $dataSourceIntegration[PartnerParams::PARAM_KEY_INTEGRATION][PartnerParams::PARAM_KEY_CANONICAL_NAME];
        $dataSourceId = $dataSourceIntegration[PartnerParams::PARAM_KEY_DATA_SOURCE][PartnerParams::PARAM_KEY_ID];
        $params = $dataSourceIntegration[PartnerParams::PARAM_KEY_ORIGINAL_PARAMS];

        $paramKeys = $dataSourceIntegration[PartnerParams::PARAM_KEY_INTEGRATION][PartnerParams::PARAM_KEY_PARAMS]; // param keys only

        /* create job data */
        $job = new \stdClass();
        $job->publisherId = $publisherId;
        $job->integrationCName = $integrationCName;
        $job->dataSourceId = $dataSourceId;
        $job->params = json_encode($params);
        $job->paramKeys = $paramKeys; // TODO: for validate params only
        $job->backFill = json_encode($backFill);

        /* create job payload. 'task' and PartnerParams::PARAM_KEY_PARAMS keys are due to worker code base */
        $payload = new \stdClass();
        $payload->task = 'executeIntegration';
        $payload->params = $job;

        try {
            if (array_key_exists(PartnerParams::PARAM_KEY_BACK_FILL, $backFill) && $backFill[PartnerParams::PARAM_KEY_BACK_FILL] == true) {
                $backFillHistoryId  = $backFill[PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_BACKFILL_HISTORY_ID];
                $this->restClient->updateBackFillHistory($backFillHistoryId, TagcadeRestClient::FETCHER_STATUS_PENDING);
            } else {
                $scheduleUUID = $backFill[PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE_UUID];
                if (!$isForceRun) {
                    $this->restClient->updateIntegrationSchedule($scheduleUUID, TagcadeRestClient::FETCHER_STATUS_PENDING);
                }
            }
            /** @var PheanstalkInterface $pheanstalk */
            $this->pheanstalk
                ->useTube($this->fetcherWorkerTube)
                ->put(
                    json_encode($payload),
                    PheanstalkInterface::DEFAULT_PRIORITY,
                    PheanstalkInterface::DEFAULT_DELAY,
                    $this->pheanstalkTTR
                );
        } catch (\Exception $e) {

        }

        return true;
    }
}