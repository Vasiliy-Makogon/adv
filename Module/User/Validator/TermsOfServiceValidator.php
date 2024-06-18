<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\User\Validator;

use Krugozor\Framework\Validator\IsNotEmptyValidator;

class TermsOfServiceValidator extends IsNotEmptyValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'TERMS_OF_SERVICE';
}