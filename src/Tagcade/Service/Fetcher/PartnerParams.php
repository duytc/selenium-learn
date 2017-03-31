<?php

namespace Tagcade\Service\Fetcher;


use Tagcade\Service\Integration\ConfigInterface;

class PartnerParams implements PartnerParamInterface
{
    /**
     * @var String
     */
    protected $username;
    /**
     * @var String
     */
    protected $password;

    /**
     * @var \DateTime
     */
    protected $startDate;

    /**
     * @var \DateTime
     */
    protected $endDate;

    /**
     * @var $config
     */
    protected $config;

    public function __construct(ConfigInterface $config)
    {
        /** @var int publisherId */
        $publisherId = $config->getPublisherId();
        /** @var string $integrationCName */
        $integrationCName = $config->getIntegrationCName();

        $username = $config->getParamValue('username', null);
        $password = $config->getParamValue('password', null);
        $reportType = $config->getParamValue('reportType', null);

        //// important: try get startDate, endDate by backFill
        if ($config->isNeedRunBackFill()) {
            $startDate = $config->getStartDateFromBackFill();

            if (!$startDate instanceof \DateTime) {
                $this->logger->error('need run backFill but backFillStartDate is invalid');
                throw new \Exception('need run backFill but backFillStartDate is invalid');
            }

            $startDateStr = $startDate->format('Y-m-d');
            $endDateStr = 'yesterday';
        } else {
            // prefer dateRange than startDate - endDate
            $dateRange = $config->getParamValue('dateRange', null);
            if (!empty($dateRange)) {
                $startDateEndDate = Config::extractDynamicDateRange($dateRange);

                if (!is_array($startDateEndDate)) {
                    // use default 'yesterday'
                    $startDateStr = 'yesterday';
                    $endDateStr = 'yesterday';
                } else {
                    $startDateStr = $startDateEndDate[0];
                    $endDateStr = $startDateEndDate[1];
                }
            } else {
                // use user modified startDate, endDate
                $startDateStr = $config->getParamValue('startDate', 'yesterday');
                $endDateStr = $config->getParamValue('endDate', 'yesterday');

                if (empty($startDateStr)) {
                    $startDateStr = 'yesterday';
                }

                if (empty($endDateStr)) {
                    $endDateStr = 'yesterday';
                }
            }
        }

        $params = [
            'username' => $username,
            'password' => $password,
            'startDate' => $startDateStr,
            'endDate' => $endDateStr,
            'reportType' => $reportType
        ];

        $processId = getmypid();
        $params['publisher_id'] = $publisherId;
        $params['partner_cname'] = $integrationCName;
        $params['process_id'] = $processId;

        /** @var PartnerParamInterface $partnerParams */
        $partnerParams = $this->createParams($params);
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return String
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return String
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }
}