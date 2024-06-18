<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Framework\Statical\Strings;

/**
 * Проверка значения (строки) на определенную длинну.
 */
class StringLengthValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'INVALID_STRING_LENGTH';

    /** @var int */
    public const ZERO_LENGTH = 0;

    /** @var int */
    public const MD5_MAX_LENGTH = 32;

    /** @var int */
    public const VARCHAR_MAX_LENGTH = 255;

    /**
     * Минимальная длинна строки.
     *
     * @var int
     */
    private int $start = 0;

    /**
     * Максимальная длинна строки.
     *
     * @var int
     */
    private int $stop = self::VARCHAR_MAX_LENGTH;

    /**
     * @param int $start
     * @return StringLengthValidator
     */
    public function setStart(int $start): self
    {
        $this->start = $start;

        return $this;
    }

    /**
     * @param int $stop
     * @return StringLengthValidator
     */
    public function setStop(int $stop): self
    {
        $this->stop = $stop;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function validate(): bool
    {
        if (Strings::isEmpty($this->value)) {
            return true;
        }

        $len = mb_strlen($this->value);

        if (!($len >= $this->start && $len <= $this->stop)) {
            $this->error_params = array('start' => $this->start, 'stop' => $this->stop);

            return false;
        }

        return true;
    }
}