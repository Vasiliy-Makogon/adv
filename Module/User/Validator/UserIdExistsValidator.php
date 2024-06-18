<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\User\Validator;

use Krugozor\Framework\Validator\AbstractValidator;

/**
 * Возвращает false (факт ошибки), если пользователь с указанным ID не найден.
 */
class UserIdExistsValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'USER_WITH_ID_NOT_EXISTS';

    /**
     * @inheritdoc
     */
    public function validate(): bool
    {
        if (!$this->mapper->findModelById($this->value)->getId()) {
            $this->error_params = ['id' => $this->value];

            return false;
        }

        return true;
    }
}