<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Framework\Statical\Strings;

/**
 * Возвращает true, если значение является корректной
 * строкой - 32-символьным именем файла на основе хэша md5.
 */
class Md5FileNameValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'INVALID_MD5_FILE_NAME';

    /**
     * @inheritdoc
     */
    public function validate(): bool
    {
        if (Strings::isEmpty($this->value)) {
            return true;
        }

        return (bool) preg_match('~^[0-9a-f]{32}\.[a-z]{2,4}$~i', $this->value);
    }
}