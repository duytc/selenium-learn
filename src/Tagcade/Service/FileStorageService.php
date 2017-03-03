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
    public function getDownloadPath(ConfigInterface $config, $fileName): string
    {
        $rootDirectory = $this->getRootDirectory();
        $publisherId = $config->getPublisherId();
        $partnerCName = $config->getIntegrationCName();

        $RunningCommandDate = new \DateTime('now');
        $myProcessId = getmypid();

        if (!is_dir($rootDirectory)) {
            mkdir($rootDirectory);
        }

        $publisherPath = sprintf('%s/%s', $rootDirectory, $publisherId);
        if (!is_dir($publisherPath)) {
            mkdir($publisherPath);
        }

        $partnerPath = $tmpPath = sprintf('%s/%s', $publisherPath, $partnerCName);
        if (!is_dir($partnerPath)) {
            mkdir($partnerPath);
        }

        $directory = sprintf('%s/%s-%s', $partnerPath, $RunningCommandDate->format('Ymd'), $myProcessId);
        if (!is_dir($directory)) {
            mkdir($directory);
        }

        $path = sprintf('%s/%s.csv', $directory, $fileName);

        $extension = 1;
        while (file_exists($path)) {
            $path = sprintf('%s/%s(%d).csv', $directory, $fileName, $extension);
            $extension++;
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