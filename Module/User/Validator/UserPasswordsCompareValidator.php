<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\User\Validator;

use Krugozor\Framework\Validator\AbstractValidator;

class UserPasswordsCompareValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'INCORRECT_PASSWORDS';

    /**
     * @inheritdoc
     */
    public function validate(): bool
    {
        return count($this->value) === 2 && count(array_unique($this->value, SORT_STRING)) === 1;
    }
}