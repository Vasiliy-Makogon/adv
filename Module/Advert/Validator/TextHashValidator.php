<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Advert\Validator;

use Krugozor\Framework\Validator\AbstractValidator;

/**
 * Проверка на наличие в базе объявления с таким же хэшем,
 * т.е. объявления с ровно таким же текстом.
 */
class TextHashValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'BAD_TEXT_HASH';

    /**
     * Возвращает false (факт ошибки), если найдено объявление с таким хэшем.
     *
     * @return bool
     */
    public function validate(): bool
    {
        $params = array(
            'where' => array('`advert_hash` = "?s"' => array($this->value->getHash())),
            'what' => 'id',
        );

        if ($this->value->getId()) {
            $params['where']['AND id <> ?i'] = array($this->value->getId());
        }

        if ($this->mapper->findModelByParams($params)->getId()) {
            return false;
        }

        return true;
    }
}