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
    public function getIntegrationToBeExecuted();

    /**
     * update last execution time for integration by canonicalName
     *
     * @param string $integrationCanonicalName
     * @return mixed
     */
    public function updateLastExecutionTimeForIntegrationByCName($integrationCanonicalName);
}