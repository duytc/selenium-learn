<?php

namespace Tagcade\Service\Fetcher\Params\VertaInternal;

interface VertaInternalPartnerParamInterface
{
    /**
     * @return string
     */
    public function getSlice();

    /**
     * @return string
     */
    public function getReportType();
}