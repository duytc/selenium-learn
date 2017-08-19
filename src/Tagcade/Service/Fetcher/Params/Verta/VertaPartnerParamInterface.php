<?php

namespace Tagcade\Service\Fetcher\Params\Verta;

interface VertaPartnerParamInterface
{
    /**
     * @return string
     */
    public function getCrossReport();

    /**
     * @return string
     */
    public function getReport();
}