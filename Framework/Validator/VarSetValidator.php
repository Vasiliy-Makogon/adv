<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Statical\Strings;

/**
 * Проверка значения на наличие в set-множестве $this->enum.
 */
class VarSetValidator extends AbstractValidator
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
     * @return $this
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

        $needle = CoverArray::fromExplode(',', $this->value)
            ->map(fn(string $value): string => trim($value))
            ->filter()
            ->unique()
            ->getDataAsArray();

        return count(array_diff($needle, $this->enum)) === 0;
    }
}