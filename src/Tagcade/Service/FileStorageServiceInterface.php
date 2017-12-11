<?php

namespace Tagcade\Service;

use Tagcade\Service\Integration\ConfigInterface;

interface FileStorageServiceInterface
{
    /**
     * @param ConfigInterface $config
     * @param $fileName
     * @param null|string $subDir the sub dir (last dir) before the file.
     * @return string
     */
    public function getDownloadPath(ConfigInterface $config, $fileName = null, $subDir = null): string;

    /**
     * @param $path
     * @param $dataRows
     * @param null $columnNames
     * @return mixed
     */
    public function saveToCSVFile($path, $dataRows, $columnNames = null);
}