<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Framework\Statical\Strings;

/**
 * Проверка значения на корректный URL-адрес.
 */
class UrlValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'INVALID_STRING_URL';

    /**
     * @inheritdoc
     */
    public function validate(): bool
    {
        if (Strings::isEmpty($this->value)) {
            return true;
        }

        return Strings::isUrl($this->value);
    }
}