<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\User\Validator;

use Krugozor\Framework\Validator\AbstractValidator;

/**
 * Возвращает false (факт ошибки), если пользователь с указанным email найден.
 */
class UserMailExistsValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'USER_MAIL_EXISTS';

    /**
     * @inheritdoc
     */
    public function validate(): bool
    {
        $params = [
            'where' => [
                'user_email = "?s"' => [$this->value->getEmail()->getValue()]
            ],
        ];

        if ($this->value->getId() !== null) {
            $params['where']['AND id <> ?i'] = [$this->value->getId()];
        }

        if ($this->mapper->findModelByParams($params)->getId()) {
            $this->error_params = ['user_email' => $this->value->getEmail()->getValue()];

            return false;
        }

        return true;
    }
}