<?php

declare(strict_types=1);

namespace Krugozor\Framework\Type\Date;

use DateTimeInterface;
use DateTimeZone;
use RuntimeException;

class DateTime extends \DateTime
{
    /**
     * Формат даты вида 'YYYY-MM-DD HH:MM:SS'
     *
     * @var string
     */
    const FORMAT_DATETIME = 'Y-m-d H:i:s';

    /**
     * Аналог \DateTime::createFromFormat, но кидает исключение, если передана некорректная дата.
     *
     * @param string $format
     * @param string $time
     * @param DateTimeZone|null $timezone
     * @return static
     */
    public static function createDateTimeFromFormat(string $format, string $time, ?DateTimeZone $timezone = null): self
    {
        $datetime = parent::createFromFormat($format, $time, $timezone);
        // Тут получаем объект \DateTime, а работаем с оберткой,
        // поэтому создаем объект self.
        if ($datetime) {
            return (new self())->setTimestamp($datetime->getTimestamp());
        }

        throw new RuntimeException(sprintf(
            'Date %s is incorrect for format %s', $time, $format
        ));
    }

    /**
     * Возвращает строку времени формата self::FORMAT_DATETIME
     *
     * @return string
     */
    public function formatAsMysqlDatetime(): string
    {
        return $this->format(self::FORMAT_DATETIME);
    }

    /**
     * Формирует дату для HTTP по Гринвичу.
     *
     * @return string
     */
    public function formatHttpDate(): string
    {
        return gmdate(DateTimeInterface::RFC7231, $this->getTimestamp());
    }

    /**
     * Функция возвращает строковое человекопонятное представление времени.
     *
     * @return string
     */
    public function formatDateForPeople(): string
    {
        $yesterday_begin = (new \DateTime('yesterday 00:00:00'))->getTimestamp();
        $yesterday_end = (new \DateTime('yesterday 23:59:59'))->getTimestamp();

        if ($this->getTimestamp() >= $yesterday_begin && $this->getTimestamp() <= $yesterday_end) {
            return 'Вчера в ' . $this->format('H:i');
        } else if ($this->getTimestamp() <= $yesterday_end) {
            return $this->format('d.m.Y H:i');
        } else {
            return 'Сегодня в ' . $this->format('H:i');
        }
    }
}