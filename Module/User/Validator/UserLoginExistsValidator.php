<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\User\Validator;

use Krugozor\Framework\Validator\AbstractValidator;

/**
 * Возвращает false (факт ошибки), если пользователь с указанным логином найден.
 */
class UserLoginExistsValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'USER_LOGIN_EXISTS';

    /**
     * @inheritDoc
     */
    public function validate(): bool
    {
        $params = [
            'where' => [
                'user_login = "?s"' => [$this->value->getLogin()]
            ],
        ];

        if ($this->value->getId() !== null) {
            $params['where']['AND id <> ?i'] = [$this->value->getId()];
        }

        if ($this->mapper->findModelByParams($params)->getId()) {
            $this->error_params = ['user_login' => $this->value->getLogin()];

            return false;
        }

        return true;
    }
}