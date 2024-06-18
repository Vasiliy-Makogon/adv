<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Framework\Statical\Strings;

/**
 * Возвращает true если значение не является null, false или пустой строкой.
 * false в противном случае.
 */
class IsNotEmptyStringValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'EMPTY_VALUE';

    /**
     * @inheritdoc
     */
    public function validate(): bool
    {
        return !Strings::isEmpty($this->value);
    }
}