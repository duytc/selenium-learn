<?php

namespace Tagcade\Service;

interface URApiServiceInterface
{
    /**
     * @param int $dataSourceId
     * @param array $rows
     * @param null $header
     * @return
     * @internal param array $columns
     */
    public function addJsonDataToDataSource($dataSourceId, array $rows, $header = null);
}