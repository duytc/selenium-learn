<?php

namespace Tagcade\Service\Fetcher\Params\VertaExternal;

interface VertaExternalPartnerParamInterface
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