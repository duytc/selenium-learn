<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui;

use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\Service\Fetcher\FetcherInterface;

interface UiFetcherInterface
{
    const TYPE = FetcherInterface::TYPE_UI;

    /**
     * Check this fetcher support this integration or not
     *
     * @param ApiParameterInterface $parameter
     * @return mixed
     */
    function supportIntegration(ApiParameterInterface $parameter);
}