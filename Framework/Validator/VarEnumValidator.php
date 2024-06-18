<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Framework\Statical\Strings;

/**
 * Проверка значения на наличие во множестве.
 */
class VarEnumValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'INCORRECT_VALUE';

    /**
     * @var array
     */
    private array $enum = [];

    /**
     * @param array $enum
     * @return VarEnumValidator
     */
    public function setEnum(array $enum): self
    {
        $this->enum = $enum;

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

        return in_array($this->value, $this->enum);
    }
}