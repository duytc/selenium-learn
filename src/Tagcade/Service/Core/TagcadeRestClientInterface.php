<?php

namespace Tagcade\Service\Core;


use DateTime;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;

interface TagcadeRestClientInterface
{
    /**
     * @param bool $force
     * @return mixed
     */
    public function getToken($force = false);

    public function getPartnerConfigurationForAllPublishers($partnerCName, $publisherId);

    /**
     * get all integrations to be executed
     *
     * @param $dataSourceId
     * @return mixed
     */
    public function getDataSourceIntegrationSchedulesToBeExecuted($dataSourceId = null);

    /**
     * get all integrations to be executed
     *
     * @param $dataSourceId
     * @return mixed
     */
    public function getDataSourceIntegrationSchedulesByDataSource($dataSourceId);

    /**
     * update last execution time for integration by canonicalName
     *
     * @param string $dataSourceIntegrationScheduleId
     * @return mixed
     */
    public function updateNextExecuteAtForIntegrationSchedule($dataSourceIntegrationScheduleId);

    /**
     * update backfill executed for integration
     *
     * @param string $dataSourceIntegrationScheduleId
     * @param $backFillStartDate
     * @param $backFillEndDate
     * @return mixed
     */
    public function updateBackFillExecutedForIntegration($dataSourceIntegrationScheduleId, $backFillStartDate, $backFillEndDate);

    /**
     * create Alert When Login Fail due to integrationConfig information
     *
     * @param int $publisherId
     * @param string $integrationCName
     * @param int $dataSourceId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param $executionDate
     * @return mixed
     */
    public function createAlertWhenLoginFail($publisherId, $integrationCName, $dataSourceId, DateTime $startDate, DateTime $endDate, DateTime $executionDate);

    /**
     * create Alert When time out due to 3rd party website
     *
     * @param int $publisherId
     * @param string $integrationCName
     * @param int $dataSourceId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param $executionDate
     * @return mixed
     */
    public function createAlertWhenTimeOut($publisherId, $integrationCName, $dataSourceId, DateTime $startDate, DateTime $endDate, $executionDate);

    /**
     * @param PartnerParamInterface $partnerParams
     */
    public function updateIntegrationLastExecutedAndBackFill($partnerParams);

    /**
     * @param $partnerParams
     */
    public function updateIntegrationIsRunningToFalse($partnerParams);

    /**
     * @param $dataSourceIntegrationScheduleId
     * @param $backFillStartDate
     * @param $backFillEndDate
     * @param $isRunning
     * @return mixed
     */
    public function updateIsRunningForBackFillHistory($dataSourceIntegrationScheduleId, $backFillStartDate, $backFillEndDate, $isRunning = false);

    /**
     * @param $dataSourceIntegrationScheduleId
     * @param $isRunning
     * @return mixed
     */
    public function updateIsRunningForIntegrationSchedule($dataSourceIntegrationScheduleId, $isRunning = false);
}