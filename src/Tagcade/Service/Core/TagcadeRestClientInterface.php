<?php

namespace Tagcade\Service\Core;


use DateTime;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Integration\IntegrationActivatorInterface;

interface TagcadeRestClientInterface
{
    const FETCHER_STATUS_NOT_RUN = 0;
    const FETCHER_STATUS_PENDING = 1;
    const FETCHER_STATUS_FINISHED = 2;
    const FETCHER_STATUS_FAILED = 3;

    /**
     * @param bool $force
     * @return mixed
     */
    public function getToken($force = false);

    /**
     * @param string $partnerCName
     * @param int $publisherId
     * @return mixed
     */
    public function getPartnerConfigurationForAllPublishers($partnerCName, $publisherId);

    /**
     * get all integrations to be executed
     *
     * @param int|null $dataSourceId
     * @return mixed
     */
    public function getDataSourceIntegrationSchedulesToBeExecuted($dataSourceId = null);


    /**
     * get all integrations to be executed with pagination
     *
     * @param IntegrationActivatorInterface $integrationActivator
     * @param int|null $dataSourceId
     * @param
     *
     * @return mixed
     */
    public function getDataSourceIntegrationSchedulesPaginationToBeExecuted(IntegrationActivatorInterface $integrationActivator, $dataSourceId = null);

    /**
     * get all integrations schedule to be executed
     *
     * @param int $dataSourceId
     * @return mixed
     */
    public function getDataSourceIntegrationSchedulesByDataSource($dataSourceId);

    /**
     * get all integrations schedule to be executed
     *
     * @param int $integrationId
     * @return mixed
     */
    public function getDataSourceIntegrationSchedulesByIntegration($integrationId);

    /**
     * create Alert When Login Fail due to integrationConfig information
     *
     * @param int $publisherId
     * @param string $integrationCName
     * @param int $dataSourceId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param DateTime $executionDate
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
     * @param DateTime $executionDate
     * @return mixed
     */
    public function createAlertWhenTimeOut($publisherId, $integrationCName, $dataSourceId, DateTime $startDate, DateTime $endDate, $executionDate);

    /**
     * @param $dataSourceIntegrationScheduleUUID
     * @param int $status
     * @return
     */
    public function updateIntegrationSchedule($dataSourceIntegrationScheduleUUID, $status = self::FETCHER_STATUS_PENDING);

    /**
     * @param PartnerParamInterface $partnerParams
     */
    public function updateIntegrationWhenDownloadSuccess(PartnerParamInterface $partnerParams);

    /**
     * @param PartnerParamInterface $partnerParams
     */
    public function updateIntegrationWhenRunFail(PartnerParamInterface $partnerParams);

    /**
     * @param $dataSourceIntegrationScheduleUUID
     * @param int $status
     * @param null $nextExecutionAt
     * @param null $finishedAt
     * @return mixed
     */
    public function updateIntegrationScheduleFinishOrFail($dataSourceIntegrationScheduleUUID, $status, $nextExecutionAt = null, $finishedAt = null);

    /**
     * @param int $dataSourceIntegrationBackFillHistoryId
     * @param int $status
     * @param null $queuedAt
     * @param null $finishedAt
     * @return mixed
     */
    public function updateBackFillHistory($dataSourceIntegrationBackFillHistoryId, $status, $queuedAt = null, $finishedAt = null);

    /**
     * create Alert When has update password
     *
     * @param int $publisherId
     * @param string $integrationCName
     * @param int $dataSourceId
     * @param string $message
     * @param DateTime $executionDate
     * @param string $username
     * @param string $url
     * @return mixed
     */
    public function createAlertWhenAppearUpdatePassword($publisherId, $integrationCName, $dataSourceId, $message, DateTime $executionDate, $username, $url);

    /**
     * @param int $dataSourceIntegrationId
     * @param int $dataSourceId
     * @return mixed
     */
    public function updateBackFillMissingDates($dataSourceIntegrationId, $dataSourceId);
}