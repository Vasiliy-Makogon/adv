<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Framework\Statical\Strings;

class TelegramValidator extends AbstractValidator
{
    /** @var int */
    public const MAX_LENGTH = 32;

    /**
     * @inheritdoc
     */
    protected string $error_key = 'INVALID_STRING_TELEGRAM';

    /**
     * @inheritdoc
     */
    public function validate(): bool
    {
        if (Strings::isEmpty($this->value)) {
            return true;
        }

        return self::isCorrectTelegram($this->value);
    }

    /**
     * @param string $in
     * @return bool
     */
    public static function isCorrectTelegram(string $in): bool
    {
        return preg_match("/^[a-z0-9_]{5,32}$/i", $in) === 1;
    }
}