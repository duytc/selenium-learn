<?php

namespace Tagcade\Service\Fetcher\Params\OptimaticInternalMarketplace;

interface OptimaticInternalMarketplacePartnerParamsInterface
{
    /**
     * @return String
     */
    public function getReportType();

    /**
     * @return String
     */
    public function getPlacements();

    /**
     * @return String
     */
    public function getPartners();
}