<?php
namespace tracky\orm\types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType as BaseDateTimeType;
use tracky\datetime\DateTime;

class DateTimeType extends BaseDateTimeType
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTime) {
            return $value->toUtc()->formatForDB();
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ["null", "Date"]);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?DateTime
    {
        if ($value === null) {
            return null;
        }

        return DateTime::fromUtc($value);
    }
}