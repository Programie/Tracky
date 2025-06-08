<?php
namespace tracky\datetime;

use Exception;

class DateRange
{
    private Date $startDate;
    private Date $endDate;

    public function __construct(Date $startDate, Date $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public static function fromString(string $startString, string $endString, string $class = DateTime::class): ?static
    {
        if ($startString === "" or $endString === "") {
            return null;
        }

        try {
            $startDate = new $class($startString);
            $endDate = new $class($endString);

            if ($startDate > $endDate) {
                return null;
            }

            return new static($startDate, $endDate);
        } catch (Exception) {
            return null;
        }
    }

    public function getStartDate(): Date
    {
        return $this->startDate;
    }

    public function getEndDate(): Date
    {
        return $this->endDate;
    }
}
