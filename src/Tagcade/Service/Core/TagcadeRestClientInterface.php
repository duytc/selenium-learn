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
    public function getDataSourceIntegrationToBeExecuted();

    /**
     * update last execution time for integration by canonicalName
     *
     * @param string $dataSourceIntegrationId
     * @param \DateTime $dateTime
     * @return mixed
     */
    public function updateLastExecutionTimeForIntegrationByCName($dataSourceIntegrationId, \DateTime $dateTime);
}