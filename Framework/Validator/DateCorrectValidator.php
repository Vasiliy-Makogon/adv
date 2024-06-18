<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use InvalidArgumentException;
use Krugozor\Framework\Statical\Strings;
use Krugozor\Framework\Type\Date\DateTime;
use RuntimeException;

/**
 * Возвращает true, если значение - объект типа Krugozor\Framework\Type\Date, строка 'now',
 * пустая строка или строка в формате $this->format.
 */
class DateCorrectValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'INVALID_DATETIME';

    /**
     * Формат проверяемой даты.
     *
     * @var string
     */
    private string $format;

    /**
     * Устанавливает формат проверяемой даты.
     *
     * @param string $format
     * @return $this
     */
    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Возвращает true, если дата в виде строки $value
     * соответствует шаблону $this->format, false в обратном случае.
     *
     * @inheritdoc
     */
    public function validate(): bool
    {
        if (Strings::isEmpty($this->value)) {
            return true;
        }

        if (is_string($this->value) && strtolower($this->value) === 'now') {
            return true;
        }

        if (is_object($this->value)) {
            if ($this->value instanceof DateTime) {
                return true;
            }

            throw new RuntimeException(sprintf(
                'Проверяемая дата не является объектом типа %s', DateTime::class
            ));
        }

        if (!$this->format) {
            throw new InvalidArgumentException('Не указан формат проверяемой даты');
        }

        $date = \DateTime::createFromFormat($this->format, $this->value);
        return $date && $date->format($this->format) == $this->value;
    }
}