<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Framework\Statical\Strings;

/**
 * Проверка числового значения на соответствие диапазону.
 */
class IntRangeValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'INVALID_INT_RANGE';

    /** @var int|null Минимальная величина диапазона. */
    private ?int $min = null;

    /** @var int|null Максимальная величина диапазона. */
    private ?int $max = null;

    /** @var int */
    const ZERO = 0;

    /** @var int */
    const ONE = 1;

    const TINYINT_MIN = -128;
    const TINYINT_MAX = 127;
    const TINYINT_MAX_UNSIGNED = 255;

    const SMALLINT_MIN = -32768;
    const SMALLINT_MAX = 32767;
    const SMALLINT_MAX_UNSIGNED = 65535;

    const MEDIUMINT_MIN = -8388608;
    const MEDIUMINT_MAX = 8388607;
    const MEDIUMINT_MAX_UNSIGNED = 16777215;

    const INT_MIN = -2147483648;
    const INT_MAX = 2147483647;
    const INT_MAX_UNSIGNED = 4294967295;

    /**
     * @param int $min
     * @return IntRangeValidator
     */
    public function setMin(int $min): self
    {
        $this->min = $min;

        return $this;
    }

    /**
     * @param int $max
     * @return IntRangeValidator
     */
    public function setMax(int $max): self
    {
        $this->max = $max;

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

        if ($this->min !== null && $this->max !== null) {
            if ($this->value < $this->min || $this->value > $this->max) {
                $this->error_params = array('min' => $this->min, 'max' => $this->max);
                return false;
            }
        }

        return true;
    }
}