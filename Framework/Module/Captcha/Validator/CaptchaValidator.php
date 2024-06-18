<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Captcha\Validator;

use Krugozor\Framework\Validator\AbstractValidator;

class CaptchaValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'BAD_CAPTCHA';

    /**
     * @inheritdoc
     */
    public function validate(): bool
    {
        return is_array($this->value)
            && count($this->value) === 2
            && count(array_unique($this->value, SORT_STRING)) === 1;
    }
}