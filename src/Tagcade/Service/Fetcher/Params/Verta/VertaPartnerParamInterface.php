<?php

namespace Tagcade\Service\Fetcher\Params\Verta;

interface VertaPartnerParamInterface
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