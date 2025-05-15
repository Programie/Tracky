<?php
namespace tracky\orm\types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateType as BaseDateType;
use tracky\datetime\Date;

class DateType extends BaseDateType
{
    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Date
    {
        if ($value === null) {
            return null;
        }

        return new Date($value);
    }
}
