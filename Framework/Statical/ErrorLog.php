<?php

declare(strict_types=1);

namespace Krugozor\Framework\Statical;

use Krugozor\Framework\Type\Date\DateTime;

class ErrorLog
{
    /**
     * @param $message
     * @return bool
     */
    public static function write($message): bool
    {
        if (!$message) {
            return false;
        }

        return error_log(sprintf(
            "%s - %s",
            (new DateTime())->formatAsMysqlDatetime(),
            $message
        ), 0);
    }
}