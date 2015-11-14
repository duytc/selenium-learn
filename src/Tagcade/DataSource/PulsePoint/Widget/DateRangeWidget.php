<?php

namespace Tagcade\DataSource\PulsePoint\Widget;

use DateTime;
use Facebook\WebDriver\WebDriverBy;
use InvalidArgumentException;

class DateRangeWidget extends AbstractWidget
{
    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return $this
     */
    public function setDateRange(DateTime $startDate, DateTime $endDate = null)
    {
        $endDate = $endDate ?: $startDate;

        if ($startDate > $endDate || $endDate < $startDate) {
            throw new InvalidArgumentException('The date range supplied is invalid');
        }

        $this->driver->findElement(WebDriverBy::id('rbCustomDates'))->click();

        $startDateWidget = new DateSelectWidget($this->driver, 'txtStartDate');
        $endDateWidget = new DateSelectWidget($this->driver, 'txtEndDate');

        if ($startDate > $endDateWidget->getDate()) {
            $endDateWidget->setDate($endDate);
            $startDateWidget->setDate($startDate);
        } else {
            $startDateWidget->setDate($startDate);
            $endDateWidget->setDate($endDate);
        }

        return $this;
    }

    /**
     * @param $fieldId
     * @param DateTime $date
     */
    protected function setDate($fieldId, DateTime $date)
    {
        (new DateSelectWidget($this->driver, $fieldId))
            ->setDate($date)
        ;
    }
}