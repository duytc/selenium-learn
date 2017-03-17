<?php

namespace Tagcade\Service\Core;


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
     * @return mixed
     */
    public function getDataSourceIntegrationSchedulesToBeExecuted();

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
     * @return mixed
     */
    public function updateBackFillExecutedForIntegration($dataSourceIntegrationScheduleId);
}