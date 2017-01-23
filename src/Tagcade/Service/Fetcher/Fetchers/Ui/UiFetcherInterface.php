<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui;

use Tagcade\Service\Fetcher\ApiParameterInterface;

interface UiFetcherInterface
{
    /**
     * Check this fetcher support this integration or not
     *
     * @param ApiParameterInterface $parameter
     * @return mixed
     */
    public function supportIntegration(ApiParameterInterface $parameter);

    /**
     * @param ApiParameterInterface $parameter
     * @return mixed
     */
    public function doGetData(ApiParameterInterface $parameter);
}