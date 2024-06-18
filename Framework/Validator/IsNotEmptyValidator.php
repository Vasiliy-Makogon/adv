<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

/**
 * Возвращает true, если значение "не пусто", т.е. попадает под конструкцию !empty
 */
class IsNotEmptyValidator extends AbstractValidator
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
        return !empty($this->value);
    }
}