<?php

namespace Tagcade\Service\Fetcher\Pages;

use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Psr\Log\LoggerInterface;
use Tagcade\Service\DownloadFileHelperInterface;
use Tagcade\Service\WebDriverService;

abstract class AbstractPage
{
    /**
     * @var RemoteWebDriver
     */
    protected $driver;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DownloadFileHelperInterface
     */
    protected $downloadFileHelper;


    protected $config;

    /**
     * @param RemoteWebDriver $driver
     * @param null $logger
     */
    public function __construct(RemoteWebDriver $driver, $logger = null)
    {
        $this->driver = $driver;
        $this->logger = $logger;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @param $downloadFileHelper
     */
    public function setDownloadFileHelper($downloadFileHelper)
    {
        $this->downloadFileHelper = $downloadFileHelper;
    }

    /**
     * @param bool $force Force reload even if the url is the current url
     * @return $this
     */
    public function navigate($force = false)
    {
        if ($this->isCurrentUrl() && !$force) {
            return $this;
        }

        $this->driver->navigate()->to(static::URL);

        return $this;
    }

    /**
     * @param bool $strict
     * @return bool
     */
    public function isCurrentUrl($strict = true)
    {
        return $strict ? $this->driver->getCurrentURL() === static::URL : strpos($this->driver->getCurrentURL(), static::URL) === 0;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return bool
     */
    protected function hasLogger()
    {
        return $this->logger instanceof LoggerInterface;
    }


    /**
     * @param RemoteWebElement $removeWebElement
     * @param $directoryStoreDownloadFile
     * @return $this
     */
    public function downloadThenWaitUntilComplete(RemoteWebElement $removeWebElement, $directoryStoreDownloadFile)
    {
        if (!$this->downloadFileHelper instanceof DownloadFileHelperInterface) {
            $this->logger->notice("Instance Helper error");
            return $this;
        }

        if (!is_dir($directoryStoreDownloadFile)) {
            $this->logger->notice(sprintf('Path to store data downnload is not directory, %s', $directoryStoreDownloadFile));
            return $this;
        }

        $this->downloadFileHelper->downloadThenWaitUntilComplete($removeWebElement, $directoryStoreDownloadFile);

        return $this;
    }


    public function getRootDirectory()
    {
        return $this->downloadFileHelper->getRootDirectory();
    }

    /**
     * @return $this|mixed
     */
    public function deleteFilesByExtension()
    {

        if (!$this->downloadFileHelper instanceof DownloadFileHelperInterface) {
            return $this;
        }

        return $this->downloadFileHelper->deleteFilesByExtension();

    }

    /**
     * PulsePoint uses a lot of ajax, this function will wait for ajax calls and also for the overlay div
     * to be removed before proceeding
     *
     * @throws NoSuchElementException
     * @throws Exception
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function waitForData()
    {
        if ($this->hasLogger()) {
            $this->logger->debug('Waiting for ajax to load');
        }

        $this->driver->wait()->until(function (RemoteWebDriver $driver) {
            return $driver->executeScript("return !!window.jQuery && window.jQuery.active == 0");
        });

        $this->sleep(2);

        $overlayPresent = $this->driver->executeScript("return !!document.querySelector('div.blockUI.blockOverlay')");

        if ($overlayPresent) {
            if ($this->hasLogger()) {
                $this->logger->debug('Waiting for overlay to disappear');
            }

            $overlaySel = WebDriverBy::cssSelector('div.blockUI.blockOverlay');
            $this->driver->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated($overlaySel));
        }

        if ($this->hasLogger()) {
            $this->logger->debug('Overlay has disappeared');
        }
    }

    public function waitForJquery()
    {
        $this->driver->wait()->until(function (RemoteWebDriver $driver) {
            return $driver->executeScript("return !!window.jQuery && window.jQuery.active == 0");
        });
    }

    public function waitForOverlay($overlayCssSelector)
    {
        $overlayPresent = $this->driver->executeScript(sprintf("return !!document.querySelector('%s')", $overlayCssSelector));

        if ($overlayPresent) {
            if ($this->hasLogger()) {
                $this->logger->debug('Waiting for overlay to disappear');
            }

            $overlaySel = WebDriverBy::cssSelector($overlayCssSelector);
            $this->driver->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated($overlaySel));
        }

        if ($this->hasLogger()) {
            $this->logger->debug('Overlay has disappeared');
        }
    }

    /**
     * @param double $seconds seconds to sleep for
     */
    public function sleep($seconds)
    {
        $seconds = (double)$seconds;

        if ($seconds <= 0) {
            return;
        }

        if ($this->hasLogger()) {
            $this->logger->debug(sprintf('Waiting for %.1f seconds', $seconds));
        }

        usleep($seconds * 1000 * 1000);
    }

    public function info($message)
    {
        if ($this->hasLogger()) {
            $this->logger->info($message);
        }

        return $this;
    }

    public function critical($message)
    {
        if ($this->hasLogger()) {
            $this->logger->critical($message);
        }

        return $this;
    }

    public function getPageUrl()
    {
        return static::URL;
    }

    protected function navigateToPartnerDomain()
    {
        $domain = parse_url($this->driver->getCurrentURL());
        $domain = $domain['host'];

        $host_names = explode(".", $domain);

        if (count($host_names) - 2 > -1) {
            $domain = $host_names[count($host_names) - 2] . "." . $host_names[count($host_names) - 1];
        } else {
            $domain = $domain['host'];
        }
        $foundSameDomain = strpos($this->getPageUrl(), $domain) > -1;
        $this->logger->debug(sprintf('Found domain in page Url (1/0) %d .Current domain %s, page to access %s', $foundSameDomain, $domain, $this->getPageUrl()));

        if (strpos($this->getPageUrl(), $domain) > -1) {
            return;
        }

        $this->navigate();
        usleep(200);

        return;
    }

    /**
     * @param $path
     * @param $dataRows
     * @throws Exception
     */
    public function arrayToCSVFile($path, $dataRows)
    {
        if (is_dir($path)) {
            throw new Exception ('Path must be file');
        }

        if (!is_array($dataRows)) {
            throw new Exception ('Data to save csv file expect array type');
        }

        $file = fopen($path, 'w');
        foreach ($dataRows as $dataRow) {
            fputcsv($file, $dataRow);
        }
        fclose($file);
    }

    /**
     * Get path to store file
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param array $config
     * @param string $fileName filename with extension, e.g abc.csv
     * @return string
     */
    protected function getDownloadPath(\DateTime $startDate, \DateTime $endDate, array $config, $fileName)
    {
        $rootDirectory = $this->downloadFileHelper->getRootDirectory();
        $publisherId = array_key_exists('publisher_id', $config) ? (int)$config['publisher_id'] : (int)$config['publisher']['id'];
        $partnerCName = array_key_exists('partner_cname', $config) ? $config['partner_cname'] : $config['networkPartner']['nameCanonical'];
        $executionDate = new \DateTime('now');
        $myProcessId = array_key_exists('process_id', $config) ? $config['process_id'] : getmypid();

        $downloadPath = sprintf(
            '%s/%d/%s/%s-%s-%s-%s',
            $rootDirectory,
            $publisherId,
            $partnerCName,
            $executionDate->format('Ymd'),
            $startDate->format('Ymd'),
            $endDate->format('Ymd'),
            $myProcessId
        );

        if (!is_dir($downloadPath)) {
            mkdir($downloadPath, 0777, true);
        }

        $path = sprintf('%s/%s.csv', $downloadPath, $fileName);

        // insert the file number when duplicate file name
        // e.g: abc.csv => abc(1).csv, abc(2).csv, ...
        $extension = 1;
        while (file_exists($path)) {
            $path = sprintf('%s/%s(%d).csv', $downloadPath, $fileName, $extension);
            $extension++;
        }

        return $path;
    }

    /**
     * get Directory Store DownloadFile.
     * The path is built base on config and startDate, endDate such as:
     * <rootDir>/<pubId>/<partner cname>/<run date>-<startDate>-<endDate>-<processId>
     * If has subDir, the path = <path above>/<subDir>
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param array $config
     * @return string
     */
    public function getDirectoryStoreDownloadFile(\DateTime $startDate, \DateTime $endDate, array $config)
    {
        $rootDirectory = $this->downloadFileHelper->getRootDirectory();
        $publisherId = array_key_exists('publisher_id', $config) ? (int)$config['publisher_id'] : (int)$config['publisher']['id'];
        $partnerCName = array_key_exists('partner_cname', $config) ? $config['partner_cname'] : $config['networkPartner']['nameCanonical'];
        $runningCommandDate = new \DateTime('now');
        $myProcessId = array_key_exists('process_id', $config) ? $config['process_id'] : getmypid();

        // append subDir if has
        $subDir = null;
        if (array_key_exists('subDir', $config)) {
            $subDir = $config['subDir'];

            if (empty($subDir)) {
                $subDir = null;
            }
        }

        $directory = WebDriverService::getDownloadPath(
            $rootDirectory,
            $publisherId,
            $partnerCName,
            $runningCommandDate,
            $startDate,
            $endDate,
            $myProcessId,
            $subDir
        );

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        return $directory;
    }

    /**
     * @param $directory
     * @return mixed
     * @throws Exception
     */
    public function getAllFileInDirectory($directory)
    {
        if (!is_dir($directory)) {
            throw new Exception(sprintf('This path is not directory, path is %s', $directory));
        }
        $filesInfo = $this->downloadFileHelper->getAllFilesInDirectory($directory);

        return $filesInfo;
    }

    /**
     * @param string $tagName
     * @param $text
     * @return RemoteWebElement
     */
    public function filterElementByTagNameAndText($tagName = 'li', $text)
    {
        /**
         * Good performance: tagName as label, li, button...
         * Bad performance if tagName = div
         */
        $classElements = $this->driver->findElements(WebDriverBy::tagName($tagName));
        if (count($classElements) < 1) {
            return null;
        }

        foreach ($classElements as $element) {
            if (!$element instanceof RemoteWebElement) {
                continue;
            }
            if (!$element->isDisplayed()) {
                continue;
            }
            if (strtolower($element->getText()) == strtolower($text)) {
                return $element;
            }
        }

        return null;
    }
}