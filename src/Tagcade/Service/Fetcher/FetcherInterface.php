<?php

namespace Tagcade\Service\Fetcher;

interface FetcherInterface
{
    const TYPE_UI = 'ui';
    const TYPE_API = 'api';

    /**
     * @param string $type
     * @return bool true if supported
     */
    public function supportType($type);

    /**
     * @param ApiParameterInterface $parameters
     * @return bool true if success
     */
    public function execute(ApiParameterInterface $parameters);
}