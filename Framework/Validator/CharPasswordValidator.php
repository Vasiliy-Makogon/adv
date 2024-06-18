<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Framework\Statical\Strings;

/**
 * Проверяет значение на присутствие недопустимых в пароле символов.
 */
class CharPasswordValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'INVALID_STRING_CHAR_PASS';

    /**
     * @inheritdoc
     */
    public function validate(): bool
    {
        if (Strings::isEmpty($this->value)) {
            return true;
        }

        return self::isCorrectCharsPass($this->value);
    }

    /**
     * Ищет в строке символы отличные от 'a-z', '0-9', '_', '-'.
     * Возвращает true в случае если ввод корректен, false в противном случае.
     *
     * @param string $in
     * @return bool
     */
    public static function isCorrectCharsPass(string $in): bool
    {
        return !preg_match("~[^a-z0-9_\-]+~i", $in);
    }
}