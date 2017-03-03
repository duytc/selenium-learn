<?php

namespace Tagcade\Service;

use Tagcade\Service\Integration\ConfigInterface;

interface FileStorageServiceInterface
{
    /**
     * @param ConfigInterface $config
     * @param $fileName
     * @return string
     */
    public function getDownloadPath(ConfigInterface $config, $fileName): string;

    /**
     * @param $path
     * @param $dataRows
     * @param null $columnNames
     * @return mixed
     */
    public function saveToCSVFile($path, $dataRows, $columnNames = null);
}