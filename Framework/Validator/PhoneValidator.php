<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Framework\Statical\Strings;

/**
 * Проверка значения на корректный телефон России, Украины, Белоруссии и Казахстана.
 */
class PhoneValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'INVALID_STRING_PHONE';

    /** @var string */
    public static string $phone_pattern_ru = '/^(\+7|8) \(\d{3}\) \d{3}-\d{2}-\d{2}$/';

    /** @var string */
    public static string $phone_pattern_ua = '/^(\+380) \(\d{2}\) \d{3}-\d{2}-\d{2}$/';

    /** @var string */
    public static string $phone_pattern_by = '/^(\+375) \(\d{2}\) \d{3}-\d{2}-\d{2}$/';

    /**
     * @param string $string
     * @return bool
     */
    public static function isRuPhoneRU(string $string): bool
    {
        return preg_match(self::$phone_pattern_ru, $string) === 1;
    }

    /**
     * @param string $string
     * @return bool
     */
    public static function isPhoneUA(string $string): bool
    {
        return preg_match(self::$phone_pattern_ua, $string) === 1;
    }

    /**
     * @param string $string
     * @return bool
     */
    public static function isPhoneBU(string $string): bool
    {
        return preg_match(self::$phone_pattern_by, $string) === 1;
    }

    /**
     * @inheritdoc
     */
    public function validate(): bool
    {
        if (Strings::isEmpty($this->value)) {
            return true;
        }

        return self::isRuPhoneRU($this->value) || self::isPhoneBU($this->value) || self::isPhoneUA($this->value);
    }
}