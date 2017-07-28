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
     * get all integrations to be executed
     *
     * @param int $dataSourceId
     * @return mixed
     */
    public function getDataSourceIntegrationSchedulesByDataSource($dataSourceId);

    /**
     * update last execution time for integration by canonicalName
     *
     * @param string $dataSourceIntegrationScheduleId
     * @return mixed
     */
    public function updateExecuteAtForIntegrationSchedule($dataSourceIntegrationScheduleId);

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
     * @param PartnerParamInterface $partnerParams
     */
    public function updateIntegrationWhenDownloadSuccess(PartnerParamInterface $partnerParams);

    /**
     * @param PartnerParamInterface $partnerParams
     */
    public function updateIntegrationWhenRunFail(PartnerParamInterface $partnerParams);

    /**
     * @param int $dataSourceIntegrationBackFillHistoryId
     * @param bool $pending
     * @param string|null $lastExecutedAt
     * @return mixed
     */
    public function updateBackFillHistory($dataSourceIntegrationBackFillHistoryId, $pending = false, $lastExecutedAt = null);

    /**
     * @param int $dataSourceIntegrationScheduleUUID
     * @param bool $pending
     * @return mixed
     */
    public function updateIntegrationSchedule($dataSourceIntegrationScheduleUUID, $pending = false);

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
}