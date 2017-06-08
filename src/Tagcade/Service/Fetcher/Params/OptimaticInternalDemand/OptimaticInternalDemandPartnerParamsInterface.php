<?php

namespace Tagcade\Service\Fetcher\Params\OptimaticInternalDemand;

interface OptimaticInternalDemandPartnerParamsInterface
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
    public function getAdvertiser();

    /**
     * @return String
     */
    public function getAllTrendByAdv();
}