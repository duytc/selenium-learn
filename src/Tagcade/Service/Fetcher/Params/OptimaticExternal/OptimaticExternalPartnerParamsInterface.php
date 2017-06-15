<?php

namespace Tagcade\Service\Fetcher\Params\OptimaticExternal;

interface OptimaticExternalPartnerParamsInterface
{
    /**
     * @return String
     */
    public function getReportType();

    /**
     * @return String
     */
    public function getPlacements();

}