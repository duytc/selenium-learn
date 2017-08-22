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
}