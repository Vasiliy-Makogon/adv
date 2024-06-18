<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Framework\Statical\Strings;

/**
 * Проверка значения на число с помощью is_numeric().
 */
class NumericValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'INVALID_NUMERIC';

    /**
     * @inheritdoc
     */
    public function validate(): bool
    {
        if (Strings::isEmpty($this->value)) {
            return true;
        }

        return is_numeric($this->value);
    }
}