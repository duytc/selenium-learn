<?php

namespace Tagcade\Service;


use Tagcade\Service\Integration\ConfigInterface;

class FileStorageService implements FileStorageServiceInterface
{
    /**
     * @var
     */
    private $rootDirectory;
    /**
     * @var
     */
    private $rootKernelDirectory;

    /**
     * FileStorageService constructor.
     * @param $rootDirectory
     * @param $rootKernelDirectory
     */
    public function __construct($rootDirectory, $rootKernelDirectory)
    {
        $this->rootDirectory = $rootDirectory;
        $this->rootKernelDirectory = $rootKernelDirectory;
    }

    /**
     * @return mixed
     */
    public function getRootDirectory()
    {
        $dataPath = $this->rootDirectory;
        $isRelativeToProjectRootDir = (strpos($dataPath, './') === 0 || strpos($dataPath, '/') !== 0);
        $dataPath = $isRelativeToProjectRootDir ? sprintf('%s/%s', rtrim($this->rootKernelDirectory, '/app'), ltrim($dataPath, './')) : $dataPath;

        return $dataPath;
    }

    /**
     * @inheritdoc
     */
    public function getDownloadPath(ConfigInterface $config, $fileName, $subDir = null): string
    {
        $rootDirectory = $this->getRootDirectory();
        $publisherId = $config->getPublisherId();
        $partnerCName = $config->getIntegrationCName();

        $executionDate = new \DateTime('now');
        $myProcessId = getmypid();

        $downloadPath = sprintf(
            '%s/%d/%s/%s-%s',
            $rootDirectory,
            $publisherId,
            $partnerCName,
            $executionDate->format('Ymd'),
            $myProcessId
        );

        // append subDir if has
        if (!empty($subDir)) {
            $downloadPath = sprintf('%s/%s', $downloadPath, $subDir);
        }

        if (!is_dir($downloadPath)) {
            mkdir($downloadPath, 0777, true);
        }

        $path = sprintf('%s/%s', $downloadPath, $fileName);

        // insert the file number when duplicate file name
        // e.g: abc.csv => abc(1).csv, abc(2).csv, ...
        $duplicatedNumber = 1;
        while (file_exists($path)) {
            $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $path = sprintf('%s/%s(%d).%s', $downloadPath, $fileNameWithoutExtension, $duplicatedNumber, $extension);
            $duplicatedNumber++;
        }

        return $path;

    }

    /**
     * @inheritdoc
     */
    public function saveToCSVFile($path, $dataRows, $columnNames = null)
    {
        if (is_dir($path)) {
            throw new \Exception ('Path must be file');
        }

        if (!is_array($columnNames)) {
            throw  new \Exception('Column names must be an array');
        }

        if (!is_array($dataRows)) {
            throw new \Exception ('Data to save csv file expect array type');
        }

        $dataRows = array_merge(array($columnNames), $dataRows);

        $file = fopen($path, 'w');
        foreach ($dataRows as $dataRow) {

            $dataRow = array_filter($dataRow, function ($column) {
                return !is_array($column);
            });

            fputcsv($file, $dataRow);
        }

        fclose($file);
    }
}