<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Framework\Statical\Strings;

/**
 * Проверка значения на корректный ник в Skype.
 */
class SkypeValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'INVALID_STRING_SKYPE';

    /**
     * @inheritdoc
     */
    public function validate(): bool
    {
        if (Strings::isEmpty($this->value)) {
            return true;
        }

        return static::isCorrectSkype($this->value);
    }

    /**
     * @param string $in
     * @return bool
     */
    public static function isCorrectSkype(string $in): bool
    {
        return preg_match('/^[a-z][a-z0-9\.,\-_]{5,31}$/i', $in) === 1;
    }
}