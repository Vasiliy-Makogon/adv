<?php

declare(strict_types=1);

namespace Krugozor\Framework\Statical;

use Krugozor\Framework\Registry;
use Krugozor\Framework\Type\Date\DateTime;

class SQLQueryLog
{
    /**
     * @param $message
     * @return int|false
     */
    public static function write($message): int|false
    {
        if (!$message) {
            return false;
        }

        return file_put_contents(
            Registry::getInstance()->get('PATH.SQL_LOG'),
            sprintf("%s - %s", (new DateTime())->formatAsMysqlDatetime(), $message . PHP_EOL),
            FILE_APPEND
        );
    }
}