<?php

namespace Tagcade\Service\Fetcher\Params\Verta;

interface VertaPartnerParamInterface
{
    /**
     * @return array
     */
    public function getCrossReports();

    /**
     * @param array $crossReports
     * @return self
     */
    public function setCrossReports(array $crossReports);

    /**
     * @return string
     */
    public function getReport();

    /**
     * @param string $report
     * @return self
     */
    public function setReport($report);

    /**
     * @return string
     */
    public function getCrossReport();

    /**
     * @param string $crossReport
     * @return self
     */
    public function setCrossReport($crossReport);
}